<?php
/** Document makers: Letterhead / Invoice / MOU — create, edit, update, view/print, delete */
$TYPES = ['letterhead'=>['Letterhead','fa-file-signature','LH'], 'invoice'=>['Invoice','fa-file-invoice-rupee','MINV'], 'mou'=>['MOU / Agreement','fa-handshake','MOU']];
$flash = '';

function doc_next_ref(string $prefix): string {
    $year = date('Y');
    $n = (int)q_val("SELECT COUNT(*) FROM documents WHERE ref_no LIKE ?", ["$prefix-$year-%"], 0) + 1;
    return sprintf('%s-%s-%03d', $prefix, $year, $n);
}

$MOU_TEMPLATE = '<h3 style="text-align:center">MEMORANDUM OF UNDERSTANDING</h3>
<p>This Memorandum of Understanding ("MOU") is entered into on <b>{DATE}</b> at Mussoorie, Uttarakhand, by and between:</p>
<p><b>FIRST PARTY:</b> {SITE_NAME}, having its place of business at {ADDRESS} (hereinafter referred to as the "First Party"); AND</p>
<p><b>SECOND PARTY:</b> {PARTY_NAME}, {PARTY_DETAILS} (hereinafter referred to as the "Second Party").</p>
<h3>1. Purpose</h3>
<p>The parties wish to set out the terms of their mutual co-operation in respect of ……</p>
<h3>2. Scope of Co-operation</h3>
<p>a) ……<br>b) ……<br>c) ……</p>
<h3>3. Commercial Terms</h3>
<p>……</p>
<h3>4. Term &amp; Termination</h3>
<p>This MOU shall remain valid from the date of signing for a period of …… months, unless terminated earlier by either party with 30 days written notice.</p>
<h3>5. Confidentiality</h3>
<p>Both parties agree to keep confidential all non-public information exchanged under this MOU.</p>
<h3>6. Governing Law</h3>
<p>This MOU shall be governed by the laws of India, with courts at Dehradun having exclusive jurisdiction.</p>
<p><br>IN WITNESS WHEREOF the parties have executed this MOU on the date first written above.</p>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('do') === 'save') {
        $type = post('doc_type');
        if (!isset($TYPES[$type])) { $flash = 'Unknown document type.'; }
        else {
            $data = [];
            if ($type === 'invoice') {
                $items = [];
                foreach ((array)($_POST['it_desc'] ?? []) as $k=>$d) {
                    if (trim($d) === '') continue;
                    $items[] = ['desc'=>trim($d), 'qty'=>(float)($_POST['it_qty'][$k] ?? 1), 'rate'=>(float)($_POST['it_rate'][$k] ?? 0)];
                }
                $data = ['items'=>$items, 'tax_percent'=>(float)post('tax_percent'), 'discount'=>(float)post('discount'), 'notes'=>post('notes'), 'paid'=>(int)(bool)post('paid')];
            } elseif ($type === 'letterhead') {
                $data = ['closing'=>post('closing'), 'signatory'=>post('signatory'), 'signatory_title'=>post('signatory_title')];
            } elseif ($type === 'mou') {
                $data = ['party1_sign'=>post('party1_sign'), 'party2_sign'=>post('party2_sign'), 'place'=>post('place'), 'witness1'=>post('witness1'), 'witness2'=>post('witness2')];
            }
            $row = [
                'doc_type'=>$type, 'ref_no'=>post('ref_no') ?: doc_next_ref($TYPES[$type][2]),
                'title'=>post('title'), 'party_name'=>post('party_name'), 'party_details'=>post('party_details'),
                'doc_date'=>post('doc_date') ?: date('Y-m-d'), 'content'=>$_POST['content'] ?? '',
                'data'=>json_encode($data, JSON_UNESCAPED_UNICODE), 'status'=>post('status') ?: 'draft',
            ];
            if ((int)post('id')) { db_update('documents', $row, 'id=?', [(int)post('id')]); $flash = $TYPES[$type][0].' updated.'; $savedId = (int)post('id'); }
            else { $row['created_by'] = (int)$ADMIN['id']; $savedId = db_insert('documents', $row); $flash = $TYPES[$type][0].' created.'; }
            $_GET['edit'] = (string)$savedId;
        }
    }
    if (post('do') === 'delete') { db_delete('documents','id=?',[(int)post('id')]); $flash='Document deleted.'; }
}

/* ============ editor screens ============ */
$new = get('new');
$edit = get('edit') ? q_row("SELECT * FROM documents WHERE id=?", [(int)get('edit')]) : null;
if (($new && isset($TYPES[$new])) || $edit) {
    $type = $edit ? $edit['doc_type'] : $new;
    $D = $edit ? (json_decode($edit['data'] ?: '{}', true) ?: []) : [];
    $label = $TYPES[$type][0];
    ?>
    <?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
    <p><a href="?m=documents&tab=<?= $type ?>">← All <?= e($label) ?>s</a>
      <?php if ($edit): ?> · <a class="btn sm sec" target="_blank" href="<?= base_url('admin/doc_print.php?id='.$edit['id']) ?>"><i class="fa-solid fa-eye"></i> View / Print</a><?php endif; ?></p>
    <form method="post" class="card"><?= csrf_field() ?>
      <input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <input type="hidden" name="doc_type" value="<?= e($type) ?>">
      <h3><i class="fa-solid <?= $TYPES[$type][1] ?>"></i> <?= $edit ? 'Edit' : 'New' ?> <?= e($label) ?></h3>
      <div class="frow4">
        <div><label>Reference No.</label><input class="input" name="ref_no" value="<?= e($edit['ref_no'] ?? doc_next_ref($TYPES[$type][2])) ?>"></div>
        <div><label>Date</label><input class="input" type="date" name="doc_date" value="<?= $edit['doc_date'] ?? date('Y-m-d') ?>"></div>
        <div><label><?= $type==='invoice' ? 'Invoice Title' : ($type==='mou' ? 'MOU Title' : 'Subject') ?></label>
          <input class="input" name="title" value="<?= e($edit['title'] ?? ($type==='mou' ? 'Memorandum of Understanding' : '')) ?>" required></div>
        <div><label>Status</label><select class="input" name="status">
          <?php foreach (['draft','final','sent'] as $st): ?><option <?= ($edit['status'] ?? 'draft')===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="frow">
        <div><label><?= $type==='invoice' ? 'Bill To (name)' : ($type==='mou' ? 'Second Party (name)' : 'To (recipient name)') ?></label>
          <input class="input" name="party_name" value="<?= e($edit['party_name'] ?? '') ?>"></div>
        <div><label><?= $type==='mou' ? 'Second Party details (address, represented by…)' : 'Address / details' ?></label>
          <input class="input" name="party_details" value="<?= e($edit['party_details'] ?? '') ?>"></div>
      </div>

      <?php if ($type === 'invoice'): ?>
        <h4 style="margin-top:16px">Line Items</h4>
        <table class="tbl doc-lines" id="itemRows">
          <tr><th style="width:50%">Description</th><th>Qty</th><th>Rate (₹)</th><th>Amount</th><th></th></tr>
          <?php $items = $D['items'] ?? [['desc'=>'','qty'=>1,'rate'=>0]]; foreach ($items as $k=>$it): ?>
          <tr>
            <td><input class="input" name="it_desc[]" value="<?= e($it['desc']) ?>" placeholder="e.g. Room charges — Victorian Suite, 2 nights"></td>
            <td><input class="input it-qty" type="number" step="0.01" name="it_qty[]" value="<?= $it['qty'] ?>" style="width:80px"></td>
            <td><input class="input it-rate" type="number" step="0.01" name="it_rate[]" value="<?= $it['rate'] ?>" style="width:110px"></td>
            <td class="it-amt" style="white-space:nowrap">—</td>
            <td><button type="button" class="btn sm danger" onclick="this.closest('tr').remove();calcInv()">✕</button></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <button type="button" class="btn ghost sm" style="margin-top:8px" onclick="addItem()">+ Add line</button>
        <div class="frow4" style="margin-top:10px">
          <div><label><?= e(setting('tax_label','GST')) ?> %</label><input class="input" type="number" step="0.01" name="tax_percent" id="taxP" value="<?= $D['tax_percent'] ?? setting('tax_percent') ?>"></div>
          <div><label>Discount (₹)</label><input class="input" type="number" step="0.01" name="discount" id="discountF" value="<?= $D['discount'] ?? 0 ?>"></div>
          <div><label>Grand Total</label><input class="input" id="grandTotal" value="—" disabled style="font-weight:700"></div>
          <div><label class="inline" style="margin-top:26px"><input type="checkbox" name="paid" value="1" <?= !empty($D['paid'])?'checked':'' ?>> Mark as PAID</label></div>
        </div>
        <label>Notes (shown at the bottom of the invoice)</label>
        <input class="input" name="notes" value="<?= e($D['notes'] ?? setting('invoice_notes')) ?>">
        <script>
        function addItem(){
          const t = document.getElementById('itemRows');
          const r = t.insertRow(-1);
          r.innerHTML = '<td><input class="input" name="it_desc[]" placeholder="Description"></td>'+
            '<td><input class="input it-qty" type="number" step="0.01" name="it_qty[]" value="1" style="width:80px"></td>'+
            '<td><input class="input it-rate" type="number" step="0.01" name="it_rate[]" value="0" style="width:110px"></td>'+
            '<td class="it-amt" style="white-space:nowrap">—</td>'+
            '<td><button type="button" class="btn sm danger" onclick="this.closest(\'tr\').remove();calcInv()">✕</button></td>';
          wire(r); calcInv();
        }
        function calcInv(){
          let sub = 0;
          document.querySelectorAll('#itemRows tr').forEach(r=>{
            const q = r.querySelector('.it-qty'), rt = r.querySelector('.it-rate'), amt = r.querySelector('.it-amt');
            if (!q) return;
            const a = (parseFloat(q.value)||0) * (parseFloat(rt.value)||0);
            amt.textContent = '₹' + a.toLocaleString('en-IN', {maximumFractionDigits:2});
            sub += a;
          });
          const tax = sub * ((parseFloat(document.getElementById('taxP').value)||0)/100);
          const disc = parseFloat(document.getElementById('discountF').value)||0;
          document.getElementById('grandTotal').value = '₹' + Math.max(0, sub + tax - disc).toLocaleString('en-IN', {maximumFractionDigits:2});
        }
        function wire(scope){ (scope||document).querySelectorAll('.it-qty,.it-rate').forEach(i=>i.addEventListener('input', calcInv)); }
        wire(); document.getElementById('taxP').addEventListener('input', calcInv);
        document.getElementById('discountF').addEventListener('input', calcInv); calcInv();
        </script>
        <label style="margin-top:14px">Extra content (optional — terms, bank details… visual editor)</label>
        <textarea class="input wysiwyg" name="content" data-minh="100px"><?= e($edit['content'] ?? '') ?></textarea>

      <?php elseif ($type === 'letterhead'): ?>
        <label>Letter Body (visual editor — the letterhead header/footer with your logo &amp; address is added automatically)</label>
        <textarea class="input wysiwyg" name="content" data-minh="280px"><?= e($edit['content'] ?? '<p>Dear Sir/Madam,</p><p><br></p><p>……</p>') ?></textarea>
        <div class="frow3">
          <div><label>Closing</label><input class="input" name="closing" value="<?= e($D['closing'] ?? 'Warm regards,') ?>"></div>
          <div><label>Signatory name</label><input class="input" name="signatory" value="<?= e($D['signatory'] ?? $ADMIN['name']) ?>"></div>
          <div><label>Signatory designation</label><input class="input" name="signatory_title" value="<?= e($D['signatory_title'] ?? 'Manager, '.setting('site_name')) ?>"></div>
        </div>

      <?php else: /* mou */ ?>
        <label>MOU Terms (visual editor — placeholders {DATE}, {SITE_NAME}, {ADDRESS}, {PARTY_NAME}, {PARTY_DETAILS} auto-fill on the printed document)</label>
        <textarea class="input wysiwyg" name="content" data-minh="340px"><?= e($edit['content'] ?? $MOU_TEMPLATE) ?></textarea>
        <div class="frow4">
          <div><label>First Party signatory</label><input class="input" name="party1_sign" value="<?= e($D['party1_sign'] ?? $ADMIN['name'].', '.setting('site_name')) ?>"></div>
          <div><label>Second Party signatory</label><input class="input" name="party2_sign" value="<?= e($D['party2_sign'] ?? '') ?>"></div>
          <div><label>Place of signing</label><input class="input" name="place" value="<?= e($D['place'] ?? 'Mussoorie') ?>"></div>
          <div></div>
          <div><label>Witness 1 (optional)</label><input class="input" name="witness1" value="<?= e($D['witness1'] ?? '') ?>"></div>
          <div><label>Witness 2 (optional)</label><input class="input" name="witness2" value="<?= e($D['witness2'] ?? '') ?>"></div>
        </div>
      <?php endif; ?>

      <button class="btn" style="margin-top:16px"><i class="fa-solid fa-floppy-disk"></i> Save <?= e($label) ?></button>
      <?php if ($edit): ?><a class="btn sec" target="_blank" href="<?= base_url('admin/doc_print.php?id='.$edit['id']) ?>">View / Print</a><?php endif; ?>
    </form>
    <?php return;
}

/* ============ list ============ */
$tab = get('tab','letterhead');
if (!isset($TYPES[$tab])) $tab = 'letterhead';
$docs = q_all("SELECT d.*, a.name creator FROM documents d LEFT JOIN admins a ON a.id=d.created_by WHERE d.doc_type=? ORDER BY d.id DESC", [$tab]);
?>
<?php if ($flash): ?><div class="alert ok"><?= e($flash) ?></div><?php endif; ?>
<div class="tabs">
  <?php foreach ($TYPES as $k=>$t): ?>
  <a href="?m=documents&tab=<?= $k ?>" class="<?= $tab===$k?'active':'' ?>"><i class="fa-solid <?= $t[1] ?>"></i> <?= $t[0] ?>s</a>
  <?php endforeach; ?>
</div>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3><?= $TYPES[$tab][0] ?>s</h3>
    <a class="btn" href="?m=documents&new=<?= $tab ?>">+ New <?= $TYPES[$tab][0] ?></a>
  </div>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>Ref No.</th><th><?= $tab==='invoice'?'Bill To':'Party / Recipient' ?></th><th>Title / Subject</th>
      <?php if ($tab==='invoice'): ?><th>Amount</th><?php endif; ?>
      <th>Date</th><th>Status</th><th>By</th><th></th></tr>
    <?php foreach ($docs as $d): $DD = json_decode($d['data'] ?: '{}', true) ?: [];
      $total = 0;
      if ($tab==='invoice') {
          foreach (($DD['items'] ?? []) as $it) $total += $it['qty']*$it['rate'];
          $total = max(0, $total * (1 + ($DD['tax_percent'] ?? 0)/100) - ($DD['discount'] ?? 0));
      } ?>
    <tr>
      <td><b><?= e($d['ref_no']) ?></b></td>
      <td><?= e($d['party_name'] ?: '—') ?></td>
      <td style="max-width:260px"><?= e(mb_substr($d['title'],0,60)) ?></td>
      <?php if ($tab==='invoice'): ?><td><b><?= money($total) ?></b> <?= !empty($DD['paid']) ? '<span class="badge green">PAID</span>' : '<span class="badge orange">unpaid</span>' ?></td><?php endif; ?>
      <td><?= $d['doc_date'] ? date('d M Y', strtotime($d['doc_date'])) : '—' ?></td>
      <td><span class="badge <?= $d['status']==='final'?'green':($d['status']==='sent'?'blue':'gray') ?>"><?= e($d['status']) ?></span></td>
      <td><?= e($d['creator'] ?: '—') ?></td>
      <td style="white-space:nowrap">
        <a class="btn sm sec" target="_blank" href="<?= base_url('admin/doc_print.php?id='.$d['id']) ?>"><i class="fa-solid fa-eye"></i> View</a>
        <a class="btn sm ghost" href="?m=documents&edit=<?= $d['id'] ?>">Edit</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this document?')"><?= csrf_field() ?>
          <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $d['id'] ?>">
          <button class="btn sm danger">✕</button></form>
      </td>
    </tr>
    <?php endforeach; if(!$docs): ?><tr><td colspan="8" class="hint">No <?= strtolower($TYPES[$tab][0]) ?>s yet — create your first one.</td></tr><?php endif; ?>
  </table></div>
  <p class="hint" style="margin-top:10px">💡 Every document opens as a print-ready A4 page on your letterhead (logo + address + GSTIN auto-added) — use the browser's Print → Save as PDF to download or share.</p>
</div>
