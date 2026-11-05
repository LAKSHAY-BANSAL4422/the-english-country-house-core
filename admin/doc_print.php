<?php
/** Print-ready A4 view for Letterhead / Invoice / MOU documents (admin & team only) */
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
db(); sess_start();
$A = require_admin('documents');
$d = q_row("SELECT * FROM documents WHERE id=?", [(int)get('id')]);
if (!$d) die('Document not found');
$D = json_decode($d['data'] ?: '{}', true) ?: [];
$c = setting('color_primary'); $s = setting('color_secondary');
$site = setting('site_name');
$addr1line = str_replace(["\r","\n"], ' ', setting('contact_address'));

/* placeholder fill for MOU/letter body */
$body = str_replace(
  ['{DATE}','{SITE_NAME}','{ADDRESS}','{PARTY_NAME}','{PARTY_DETAILS}','{PHONE}','{EMAIL}','{GSTIN}'],
  [date('d F Y', strtotime($d['doc_date'] ?: 'now')), $site, $addr1line, $d['party_name'], $d['party_details'], setting('contact_phone'), setting('contact_email'), setting('gstin')],
  $d['content'] ?? '');

$sub = 0; $tax = 0; $disc = 0; $grand = 0;
if ($d['doc_type'] === 'invoice') {
    foreach (($D['items'] ?? []) as $it) $sub += $it['qty'] * $it['rate'];
    $tax = $sub * (($D['tax_percent'] ?? 0)/100);
    $disc = (float)($D['discount'] ?? 0);
    $grand = max(0, $sub + $tax - $disc);
}
$typeLabel = ['letterhead'=>'', 'invoice'=>'INVOICE', 'mou'=>'MEMORANDUM OF UNDERSTANDING'][$d['doc_type']];
?><!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($d['ref_no']) ?> — <?= e($d['title']) ?></title>
<style>
*{box-sizing:border-box}
body{font-family:'Segoe UI',Georgia,serif;color:#2b333d;background:#e9e6df;margin:0;padding:20px 10px}
.sheet{background:#fff;width:210mm;max-width:100%;min-height:280mm;margin:0 auto;box-shadow:0 14px 44px rgba(15,34,56,.18);display:flex;flex-direction:column}
.lh-top{display:flex;align-items:center;gap:18px;padding:26px 34px 18px;border-bottom:3px double <?= $c ?>}
.lh-top img{max-height:70px}
.lh-name{font-family:Georgia,serif;font-size:26px;color:<?= $s ?>;margin:0}
.lh-tag{font-size:11px;letter-spacing:3px;text-transform:uppercase;color:<?= $c ?>}
.lh-contact{margin-left:auto;text-align:right;font-size:11.5px;color:#66707d;line-height:1.7}
.doc-body{padding:26px 34px;flex:1;font-size:14px;line-height:1.75}
.doc-body h2,.doc-body h3{color:<?= $s ?>}
.doc-body blockquote{border-left:3px solid <?= $c ?>;margin:10px 0;padding:6px 14px;background:#faf7f2}
.doc-body img{max-width:100%}
.meta-row{display:flex;justify-content:space-between;font-size:13px;margin-bottom:16px;flex-wrap:wrap;gap:6px}
.doc-title{text-align:center;font-size:19px;letter-spacing:4px;color:<?= $s ?>;border:2px solid <?= $c ?>;display:inline-block;padding:6px 26px;border-radius:4px;margin:0 auto 18px}
.inv-table{width:100%;border-collapse:collapse;font-size:13.5px;margin:14px 0}
.inv-table th{background:#f6f3ee;border-bottom:2px solid <?= $c ?>;text-align:left;padding:9px 11px}
.inv-table td{border-bottom:1px solid #eee;padding:9px 11px}
.inv-table .tot td{font-weight:700;background:#fdf9f1;font-size:15px}
.paid-stamp{position:absolute;transform:rotate(-14deg);border:4px solid #1d7d4d;color:#1d7d4d;font-size:30px;font-weight:800;letter-spacing:6px;padding:6px 24px;border-radius:8px;opacity:.55;right:60px;top:280px}
.sign-grid{display:flex;justify-content:space-between;gap:30px;margin-top:60px}
.sign-box{flex:1;text-align:center;font-size:13px}
.sign-line{border-top:1.5px solid #444;margin-bottom:6px;padding-top:6px}
.lh-foot{border-top:3px double <?= $c ?>;padding:12px 34px;font-size:11px;color:#66707d;text-align:center;line-height:1.7;background:#fbfaf7}
.toolbar{max-width:210mm;margin:0 auto 14px;display:flex;gap:10px}
.toolbar a,.toolbar button{background:<?= $c ?>;color:#fff;border:none;border-radius:7px;padding:11px 24px;font-size:14px;cursor:pointer;text-decoration:none;font-family:inherit}
.toolbar .ghost{background:#fff;color:#333;border:1px solid #ccc}
@media print{body{background:#fff;padding:0}.toolbar{display:none}.sheet{box-shadow:none;width:auto;min-height:auto}}
</style></head><body>
<div class="toolbar noprint">
  <button onclick="print()">🖨 Print / Save PDF</button>
  <a class="ghost" href="<?= base_url('admin/index.php?m=documents&edit='.$d['id']) ?>">✏ Edit</a>
  <a class="ghost" href="<?= base_url('admin/index.php?m=documents&tab='.$d['doc_type']) ?>">← Back to list</a>
</div>
<div class="sheet" style="position:relative">
  <div class="lh-top">
    <?php $lgp = setting('logo_print') ?: setting('logo_header'); if ($lgp): ?><img src="<?= base_url($lgp) ?>" alt=""><?php endif; ?>
    <div><h1 class="lh-name"><?= e($site) ?></h1><div class="lh-tag"><?= e(setting('site_tagline')) ?></div></div>
    <div class="lh-contact"><?= nl2br(e(setting('contact_address'))) ?><br>☎ <?= e(setting('contact_phone')) ?> · ✉ <?= e(setting('contact_email')) ?>
      <?= setting('gstin') ? '<br>GSTIN: '.e(setting('gstin')) : '' ?></div>
  </div>

  <div class="doc-body">
    <?php if ($d['doc_type'] === 'invoice' && !empty($D['paid'])): ?><div class="paid-stamp">PAID</div><?php endif; ?>
    <div class="meta-row">
      <span><b>Ref:</b> <?= e($d['ref_no']) ?></span>
      <span><b>Date:</b> <?= date('d F Y', strtotime($d['doc_date'] ?: 'now')) ?></span>
    </div>

    <?php if ($typeLabel): ?><div style="text-align:center"><span class="doc-title"><?= $typeLabel ?></span></div><?php endif; ?>

    <?php if ($d['doc_type'] === 'letterhead'): ?>
      <?php if ($d['party_name'] || $d['party_details']): ?>
        <p><b>To,</b><br><?= e($d['party_name']) ?><?= $d['party_details'] ? '<br>'.e($d['party_details']) : '' ?></p>
      <?php endif; ?>
      <p><b>Subject: <?= e($d['title']) ?></b></p>
      <div><?= $body ?></div>
      <div style="margin-top:44px">
        <p><?= e($D['closing'] ?? 'Warm regards,') ?></p>
        <p style="margin-top:44px"><b><?= e($D['signatory'] ?? '') ?></b><br><span style="color:#66707d;font-size:13px"><?= e($D['signatory_title'] ?? '') ?></span></p>
      </div>

    <?php elseif ($d['doc_type'] === 'invoice'): ?>
      <div class="meta-row" style="margin-top:8px">
        <span style="max-width:55%"><b>Bill To:</b><br><?= e($d['party_name']) ?><?= $d['party_details'] ? '<br><span style="color:#66707d;font-size:12.5px">'.e($d['party_details']).'</span>' : '' ?></span>
        <span style="text-align:right"><b><?= e($d['title']) ?></b><br><span style="color:#66707d;font-size:12.5px">Status: <?= !empty($D['paid']) ? 'PAID' : 'UNPAID' ?></span></span>
      </div>
      <table class="inv-table">
        <tr><th style="width:46%">Description</th><th>Qty</th><th>Rate</th><th style="text-align:right">Amount</th></tr>
        <?php foreach (($D['items'] ?? []) as $it): ?>
        <tr><td><?= e($it['desc']) ?></td><td><?= rtrim(rtrim(number_format($it['qty'],2),'0'),'.') ?></td>
          <td><?= money($it['rate']) ?></td><td style="text-align:right"><?= money($it['qty']*$it['rate']) ?></td></tr>
        <?php endforeach; ?>
        <tr><td colspan="3" style="text-align:right">Subtotal</td><td style="text-align:right"><?= money($sub) ?></td></tr>
        <?php if (($D['tax_percent'] ?? 0) > 0): ?>
        <tr><td colspan="3" style="text-align:right">CGST (<?= ($D['tax_percent'])/2 ?>%)</td><td style="text-align:right"><?= money($tax/2) ?></td></tr>
        <tr><td colspan="3" style="text-align:right">SGST (<?= ($D['tax_percent'])/2 ?>%)</td><td style="text-align:right"><?= money($tax/2) ?></td></tr>
        <?php endif; ?>
        <?php if ($disc > 0): ?><tr><td colspan="3" style="text-align:right">Discount</td><td style="text-align:right">− <?= money($disc) ?></td></tr><?php endif; ?>
        <tr class="tot"><td colspan="3" style="text-align:right">Grand Total</td><td style="text-align:right"><?= money($grand) ?></td></tr>
      </table>
      <?php if ($body): ?><div style="font-size:13px"><?= $body ?></div><?php endif; ?>
      <?php if (!empty($D['notes'])): ?><p style="font-size:12px;color:#66707d;margin-top:14px"><?= e($D['notes']) ?></p><?php endif; ?>
      <div class="sign-grid" style="margin-top:70px">
        <div class="sign-box"></div><div class="sign-box"></div>
        <div class="sign-box"><div class="sign-line">Authorised Signatory</div><?= e($site) ?></div>
      </div>

    <?php else: /* mou */ ?>
      <div><?= $body ?></div>
      <div class="sign-grid">
        <div class="sign-box"><div class="sign-line"><b>First Party</b></div><?= e($D['party1_sign'] ?? $site) ?></div>
        <div class="sign-box"><div class="sign-line"><b>Second Party</b></div><?= e(($D['party2_sign'] ?? '') ?: $d['party_name']) ?></div>
      </div>
      <?php if (!empty($D['witness1']) || !empty($D['witness2'])): ?>
      <div class="sign-grid" style="margin-top:50px">
        <div class="sign-box"><div class="sign-line">Witness 1</div><?= e($D['witness1'] ?? '') ?></div>
        <div class="sign-box"><div class="sign-line">Witness 2</div><?= e($D['witness2'] ?? '') ?></div>
      </div>
      <?php endif; ?>
      <p style="font-size:12px;color:#66707d;margin-top:26px">Signed at <?= e($D['place'] ?? 'Mussoorie') ?> on <?= date('d F Y', strtotime($d['doc_date'] ?: 'now')) ?>.</p>
    <?php endif; ?>
  </div>

  <div class="lh-foot"><?= e($site) ?> · <?= e($addr1line) ?> · ☎ <?= e(setting('contact_phone')) ?> · <?= e(setting('contact_email')) ?> · <?= e(parse_url(base_url(), PHP_URL_HOST)) ?></div>
</div>
</body></html>
