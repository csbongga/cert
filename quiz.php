<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$courseId = (int)($_GET['course'] ?? $_POST['course'] ?? 0);
$stmt = db()->prepare('SELECT * FROM courses WHERE id = ? AND is_active = 1');
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) { http_response_code(404); die('ไม่พบหลักสูตร'); }

$uid = current_user()['id'];

// ถ้ามีใบประกาศแล้ว ไม่ต้องทำซ้ำ
$s = db()->prepare('SELECT id FROM certificates WHERE user_id = ? AND course_id = ?');
$s->execute([$uid, $courseId]);
if ($s->fetch()) redirect('/course.php?id=' . $courseId);

/* ---------------- ตรวจคำตอบ (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $quiz = $_SESSION['quiz'] ?? null;
    if (!$quiz || (int)$quiz['course_id'] !== $courseId) {
        flash('error', 'ข้อสอบหมดอายุ กรุณาเริ่มทำใหม่');
        redirect('/quiz.php?course=' . $courseId);
    }

    $answers = $_POST['answers'] ?? [];
    $score = 0;
    $total = count($quiz['questions']);
    foreach ($quiz['questions'] as $qid => $correctChoiceId) {
        if (isset($answers[$qid]) && (int)$answers[$qid] === (int)$correctChoiceId) {
            $score++;
        }
    }
    $passed = $score >= (int)$course['pass_score'];

    // บันทึกผลสอบ
    $ins = db()->prepare(
        'INSERT INTO attempts (user_id, course_id, score, total, passed) VALUES (?, ?, ?, ?, ?)'
    );
    $ins->execute([$uid, $courseId, $score, $total, $passed ? 1 : 0]);
    $attemptId = (int)db()->lastInsertId();

    // ออกใบประกาศถ้าผ่าน (กันซ้ำด้วย UNIQUE key)
    if ($passed) {
        $code = 'CERT-' . date('Y') . '-' . str_pad((string)$courseId, 3, '0', STR_PAD_LEFT)
                . '-' . str_pad((string)$uid, 4, '0', STR_PAD_LEFT);
        try {
            $c = db()->prepare(
                'INSERT INTO certificates (user_id, course_id, attempt_id, cert_code) VALUES (?, ?, ?, ?)'
            );
            $c->execute([$uid, $courseId, $attemptId, $code]);
        } catch (PDOException $e) { /* มีอยู่แล้ว ไม่เป็นไร */ }
    }

    unset($_SESSION['quiz']);
    $_SESSION['last_result'] = [
        'course_id' => $courseId, 'score' => $score, 'total' => $total, 'passed' => $passed,
    ];
    redirect('/quiz.php?course=' . $courseId . '&result=1');
}

/* ---------------- แสดงผลลัพธ์ ---------------- */
if (isset($_GET['result']) && isset($_SESSION['last_result'])) {
    $r = $_SESSION['last_result'];
    unset($_SESSION['last_result']);
    $pageTitle = 'ผลการทดสอบ';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="result-hero">
      <?php if ($r['passed']): ?>
        <div style="font-size:3rem">🎉</div>
        <h1 class="page-title">ยินดีด้วย! คุณสอบผ่าน</h1>
        <div class="result-score pass"><?= (int)$r['score'] ?>/<?= (int)$r['total'] ?></div>
        <p class="muted">คุณสามารถดาวน์โหลดใบประกาศได้แล้ว</p>
        <a class="btn btn-lg" href="<?= url('/certificate.php?course=' . $courseId) ?>">⬇️ ดาวน์โหลดใบประกาศ (PDF)</a>
      <?php else: ?>
        <div style="font-size:3rem">📄</div>
        <h1 class="page-title">ยังไม่ผ่านเกณฑ์</h1>
        <div class="result-score fail"><?= (int)$r['score'] ?>/<?= (int)$r['total'] ?></div>
        <p class="muted">ต้องได้อย่างน้อย <?= (int)$course['pass_score'] ?> ข้อ ลองอีกครั้งได้เลย</p>
        <a class="btn btn-lg" href="<?= url('/quiz.php?course=' . $courseId) ?>">🔄 ทำแบบทดสอบใหม่</a>
      <?php endif; ?>
      <p class="mt"><a href="<?= url('/course.php?id=' . $courseId) ?>">← กลับหน้าหลักสูตร</a></p>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ---------------- สร้างชุดข้อสอบ (GET) ---------------- */
$limit = max(1, (int)$course['quiz_count']);
$rows = db()->query(
    'SELECT id, question FROM questions WHERE course_id = ' . (int)$courseId . ' ORDER BY RAND() LIMIT ' . $limit
)->fetchAll();

if (!$rows) { flash('error', 'หลักสูตรนี้ยังไม่มีคำถาม'); redirect('/course.php?id=' . $courseId); }

$quizQuestions = [];   // qid => correct choice id  (เก็บใน session ไว้ตรวจ)
$display = [];
foreach ($rows as $q) {
    $choices = db()->prepare('SELECT id, choice_text, is_correct FROM choices WHERE question_id = ? ORDER BY RAND()');
    $choices->execute([$q['id']]);
    $chs = $choices->fetchAll();
    foreach ($chs as $ch) {
        if ($ch['is_correct']) $quizQuestions[$q['id']] = (int)$ch['id'];
    }
    $display[] = ['id' => $q['id'], 'question' => $q['question'], 'choices' => $chs];
}
$_SESSION['quiz'] = ['course_id' => $courseId, 'questions' => $quizQuestions];

$pageTitle = 'แบบทดสอบ · ' . $course['title'];
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">แบบทดสอบ: <?= e($course['title']) ?></h1>
<p class="page-sub">เลือกคำตอบให้ครบทุกข้อ ต้องได้ <?= (int)$course['pass_score'] ?> จาก <?= count($display) ?> ข้อจึงจะผ่าน</p>

<form method="post" action="<?= url('/quiz.php?course=' . $courseId) ?>" id="quizForm">
  <?= csrf_field() ?>
  <input type="hidden" name="course" value="<?= (int)$courseId ?>">
  <?php foreach ($display as $i => $q): ?>
    <div class="quiz-q">
      <h4><?= ($i + 1) ?>. <?= e($q['question']) ?></h4>
      <?php foreach ($q['choices'] as $ch): ?>
        <label class="choice">
          <input type="radio" name="answers[<?= (int)$q['id'] ?>]" value="<?= (int)$ch['id'] ?>" required>
          <?= e($ch['choice_text']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-lg" type="submit">ส่งคำตอบ</button>
</form>

<script>
// ไฮไลต์ตัวเลือกที่เลือก
document.querySelectorAll('.quiz-q').forEach(function (q) {
  q.querySelectorAll('.choice').forEach(function (lbl) {
    lbl.addEventListener('click', function () {
      q.querySelectorAll('.choice').forEach(function (l) { l.classList.remove('selected'); });
      lbl.classList.add('selected');
    });
  });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
