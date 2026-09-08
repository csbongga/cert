<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$courseId = (int)($_GET['course'] ?? 0);
$s = db()->prepare('SELECT * FROM courses WHERE id = ?');
$s->execute([$courseId]);
$course = $s->fetch();
if (!$course) { die('ไม่พบหลักสูตร'); }

/* ---------- ลบคำถาม ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $qid = (int)$_POST['question_id'];
    $del = db()->prepare('DELETE FROM questions WHERE id = ? AND course_id = ?');
    $del->execute([$qid, $courseId]);
    flash('success', 'ลบคำถามแล้ว');
    redirect('/admin/questions.php?course=' . $courseId);
}

/* ---------- บันทึกคำถาม (เพิ่ม/แก้ไข) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    verify_csrf();
    $qid      = (int)($_POST['question_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $choices  = $_POST['choices'] ?? [];
    $correct  = $_POST['correct'] ?? '';

    // เก็บเฉพาะช้อยส์ที่ไม่ว่าง (คงลำดับ index เดิมไว้เทียบกับ correct)
    $clean = [];
    foreach ($choices as $i => $text) {
        $text = trim($text);
        if ($text !== '') $clean[$i] = $text;
    }

    $errors = [];
    if ($question === '') $errors[] = 'กรุณากรอกคำถาม';
    if (count($clean) < 2) $errors[] = 'ต้องมีตัวเลือกอย่างน้อย 2 ข้อ';
    if ($correct === '' || !isset($clean[$correct])) $errors[] = 'กรุณาเลือกข้อที่เป็นคำตอบที่ถูก';

    if ($errors) {
        foreach ($errors as $er) flash('error', $er);
        redirect('/admin/questions.php?course=' . $courseId . ($qid ? '&edit=' . $qid : ''));
    }

    $pdo = db();
    $pdo->beginTransaction();
    if ($qid) {
        $pdo->prepare('UPDATE questions SET question = ? WHERE id = ? AND course_id = ?')
            ->execute([$question, $qid, $courseId]);
        $pdo->prepare('DELETE FROM choices WHERE question_id = ?')->execute([$qid]);
    } else {
        $pdo->prepare('INSERT INTO questions (course_id, question) VALUES (?, ?)')
            ->execute([$courseId, $question]);
        $qid = (int)$pdo->lastInsertId();
    }
    $cStmt = $pdo->prepare('INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)');
    foreach ($clean as $i => $text) {
        $cStmt->execute([$qid, $text, (string)$i === (string)$correct ? 1 : 0]);
    }
    $pdo->commit();

    flash('success', 'บันทึกคำถามเรียบร้อย');
    redirect('/admin/questions.php?course=' . $courseId);
}

/* ---------- โหลดข้อมูลสำหรับฟอร์มแก้ไข ---------- */
$editQ = null; $editChoices = [];
if (isset($_GET['edit'])) {
    $eq = (int)$_GET['edit'];
    $s = db()->prepare('SELECT * FROM questions WHERE id = ? AND course_id = ?');
    $s->execute([$eq, $courseId]);
    $editQ = $s->fetch() ?: null;
    if ($editQ) {
        $s = db()->prepare('SELECT * FROM choices WHERE question_id = ? ORDER BY id');
        $s->execute([$eq]);
        $editChoices = $s->fetchAll();
    }
}

/* ---------- รายการคำถามทั้งหมด ---------- */
$questions = db()->prepare('SELECT * FROM questions WHERE course_id = ? ORDER BY id');
$questions->execute([$courseId]);
$questions = $questions->fetchAll();
$choicesByQ = [];
if ($questions) {
    $ids = implode(',', array_map('intval', array_column($questions, 'id')));
    foreach (db()->query('SELECT * FROM choices WHERE question_id IN (' . $ids . ') ORDER BY id') as $ch) {
        $choicesByQ[$ch['question_id']][] = $ch;
    }
}

// เตรียมค่าเริ่มต้นของช่องช้อยส์ (อย่างน้อย 4 ช่อง)
$slotTexts = ['', '', '', ''];
$correctSlot = '';
if ($editQ) {
    $slotTexts = [];
    foreach ($editChoices as $i => $c) {
        $slotTexts[$i] = $c['choice_text'];
        if ($c['is_correct']) $correctSlot = (string)$i;
    }
    while (count($slotTexts) < 4) $slotTexts[] = '';
}

$pageTitle = 'ข้อสอบ · ' . $course['title'];
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <h1 class="page-title" style="margin:0">ข้อสอบ: <?= e($course['title']) ?></h1>
  <a class="btn-ghost" href="<?= url('/admin/course_edit.php?id=' . $courseId) ?>">← ตั้งค่าหลักสูตร</a>
</div>
<p class="page-sub">มีคำถาม <?= count($questions) ?> ข้อ · ระบบจะสุ่ม <?= (int)$course['quiz_count'] ?> ข้อให้ผู้เรียน (ควรมีคำถาม ≥ จำนวนที่สุ่ม)</p>

<div class="form-card wide" style="margin-left:0" id="qform">
  <h3><?= $editQ ? '✏️ แก้ไขคำถาม' : '➕ เพิ่มคำถามใหม่' ?></h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="question_id" value="<?= $editQ ? (int)$editQ['id'] : '' ?>">
    <div class="field">
      <label>คำถาม</label>
      <textarea name="question" required><?= $editQ ? e($editQ['question']) : '' ?></textarea>
    </div>
    <label>ตัวเลือก (เลือกวงกลมหน้าข้อที่เป็น <b>คำตอบที่ถูก</b>)</label>
    <?php foreach ($slotTexts as $i => $txt): ?>
      <div class="field" style="display:flex;align-items:center;gap:10px">
        <input type="radio" name="correct" value="<?= $i ?>" <?= (string)$i === $correctSlot ? 'checked' : '' ?> style="width:auto;flex:0">
        <input type="text" name="choices[<?= $i ?>]" value="<?= e($txt) ?>" placeholder="ตัวเลือกที่ <?= $i + 1 ?><?= $i >= 2 ? ' (ไม่บังคับ)' : '' ?>">
      </div>
    <?php endforeach; ?>
    <div class="actions">
      <button class="btn" type="submit"><?= $editQ ? 'บันทึกการแก้ไข' : 'เพิ่มคำถาม' ?></button>
      <?php if ($editQ): ?><a class="btn-ghost" href="<?= url('/admin/questions.php?course=' . $courseId) ?>">ยกเลิก</a><?php endif; ?>
    </div>
  </form>
</div>

<h3 class="mt">คำถามทั้งหมด</h3>
<?php if (!$questions): ?>
  <p class="muted">ยังไม่มีคำถาม เพิ่มคำถามแรกด้านบนได้เลย</p>
<?php else: ?>
  <?php foreach ($questions as $n => $q): ?>
    <div class="quiz-q">
      <div class="toolbar" style="margin-bottom:8px">
        <h4 style="margin:0"><?= $n + 1 ?>. <?= e($q['question']) ?></h4>
        <div class="actions">
          <a class="btn-ghost" href="<?= url('/admin/questions.php?course=' . $courseId . '&edit=' . $q['id']) ?>#qform">แก้ไข</a>
          <form method="post" onsubmit="return confirm('ลบคำถามนี้?')" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
            <button class="btn btn-danger" type="submit">ลบ</button>
          </form>
        </div>
      </div>
      <ul style="margin:0;padding-left:20px">
        <?php foreach ($choicesByQ[$q['id']] ?? [] as $ch): ?>
          <li style="<?= $ch['is_correct'] ? 'color:#166534;font-weight:600' : '' ?>">
            <?= e($ch['choice_text']) ?><?= $ch['is_correct'] ? ' ✔ (เฉลย)' : '' ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
