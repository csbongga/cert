<?php
/**
 * สร้าง HTML ของใบประกาศ (ใช้ทั้งกับ mPDF และหน้าพิมพ์สำรอง)
 * กระดาษ A4 แนวนอน 297 x 210 mm
 * ทุกองค์ประกอบใช้ position:absolute อ้างอิงหน้ากระดาษโดยตรง (เป็นลูกของ body)
 * เพื่อให้ mPDF วางข้อความซ้อนบนรูปได้ในหน้าเดียว
 */

function render_certificate_html(array $course, array $user, array $cert, string $imgSrc): string
{
    $name   = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
    $title  = htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8');
    $code   = htmlspecialchars($cert['cert_code'], ENT_QUOTES, 'UTF-8');
    $issued = htmlspecialchars(thai_date($cert['issued_at']), ENT_QUOTES, 'UTF-8');

    $nameTop    = (float)$course['name_top'];
    $nameSize   = (int)$course['name_size'];
    $courseTop  = (float)$course['course_top'];
    $courseSize = (int)$course['course_size'];
    $showMeta   = (int)$course['show_meta'] === 1;
    $metaTop    = 198.0; // mm จากขอบบน

    // บรรทัดรายละเอียดการอบรม (วันที่/สถานที่) — แสดงเมื่อมีข้อความ
    $detailText = trim((string)($course['detail_text'] ?? ''));
    $detailTop  = (float)($course['detail_top'] ?? 133);
    $detailSize = (int)($course['detail_size'] ?? 15);
    $detail = '';
    if ($detailText !== '') {
        $detail = '
    <div class="detail">' . htmlspecialchars($detailText, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    $meta = '';
    if ($showMeta) {
        $meta = '
    <div class="meta metaL">เลขที่ ' . $code . '</div>
    <div class="meta metaR">ออกให้ ณ วันที่ ' . $issued . '</div>';
    }

    return '<!doctype html>
<html lang="th"><head><meta charset="utf-8">
<style>
  @page { size: 297mm 210mm; margin: 0; }
  html, body { margin: 0; padding: 0; width: 297mm; height: 210mm; }
  .bg   { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
  .name {
    position: absolute; left: 0; top: ' . $nameTop . 'mm; width: 297mm;
    text-align: center; font-size: ' . $nameSize . 'pt; font-weight: bold; color: #1f2937;
  }
  .course {
    position: absolute; left: 0; top: ' . $courseTop . 'mm; width: 297mm;
    text-align: center; font-size: ' . $courseSize . 'pt; color: #374151;
  }
  .detail {
    position: absolute; left: 0; top: ' . $detailTop . 'mm; width: 297mm;
    text-align: center; font-size: ' . $detailSize . 'pt; color: #4b5563;
  }
  .meta  { position: absolute; top: ' . $metaTop . 'mm; font-size: 11pt; color: #6b7280; }
  .metaL { left: 20mm; }
  .metaR { left: 0; width: 277mm; text-align: right; }
</style></head>
<body>
  <img class="bg" src="' . $imgSrc . '" alt="">
  <div class="name">' . $name . '</div>
  <div class="course">' . $title . '</div>' . $detail . $meta . '
</body></html>';
}
