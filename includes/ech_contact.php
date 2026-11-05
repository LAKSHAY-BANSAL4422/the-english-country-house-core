<?php
/**
 * Official contact number + phone validation shared by the whole site (round 5).
 * Loaded by includes/functions.php, so every page, API endpoint, email and invoice sees the same values.
 *
 * 1. ECH_WHATSAPP_NUMBER is the one WhatsApp destination for every click-to-chat link (wa_link(), the enquiry form,
 *    the floating button, the chat bot, food-order hand-off, emails). Confirmed by the client: +91 80761 85555.
 *    ECH_OLD_NUMBERS lists retired hotel numbers: wherever one is still stored in Admin → Settings (contact phone,
 *    WhatsApp number, menu numbers) it is read as the official number instead. The database itself is not changed.
 * 2. phone_check() is the server-side half of the site-wide phone rule; assets/js/phone.js is the same rule in the
 *    browser. Keep the two in step.
 */

const ECH_WHATSAPP_NUMBER = '918076185555';      // digits only, international (wa.me format)
const ECH_PHONE_DISPLAY   = '+91 80761 85555';   // how the number is printed
const ECH_OLD_NUMBERS     = ['9456765555'];      // retired numbers (last 10 digits)

/** The official WhatsApp number, digits only. */
function ech_whatsapp(): string { return ECH_WHATSAPP_NUMBER; }

/** True when $num is one of the retired hotel numbers, in any format (+91 94567 65555, 919456765555, 09456765555…). */
function ech_is_old_number(string $num): bool {
    $d = preg_replace('/\D/', '', $num);
    return strlen($d) >= 10 && in_array(substr($d, -10), ECH_OLD_NUMBERS, true);
}

/** Settings that hold the hotel's own phone / WhatsApp number: a retired value reads as the official one. */
function ech_contact_setting(string $key, $value) {
    if (!in_array($key, ['contact_phone', 'contact_phone2', 'menu_order_phone', 'wa_number', 'menu_wa_numbers'], true)) return $value;
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')) return $value;   // Admin → Settings shows what is really stored
    if ($key === 'menu_wa_numbers') {
        $list = json_decode((string)$value, true);
        if (!is_array($list)) return $value;
        foreach ($list as &$n) if (ech_is_old_number((string)($n['number'] ?? ''))) $n['number'] = ECH_WHATSAPP_NUMBER;
        unset($n);
        return json_encode($list);
    }
    if (!ech_is_old_number((string)$value)) return $value;
    return $key === 'wa_number' ? ECH_WHATSAPP_NUMBER : ECH_PHONE_DISPLAY;
}

/**
 * Site-wide phone rule. Returns ['ok'=>bool, 'e164'=>'+91XXXXXXXXXX' or '', 'error'=>message or ''].
 * - Allowed characters: digits, spaces, dashes, dots, brackets and one leading "+".
 * - India: mobiles are 10 digits starting 6–9, optionally written with +91, 91 or a leading 0; landlines are
 *   accepted with their 0 + STD code (11 digits, e.g. 0135 263 2100).
 * - International guests: "+" and country code, 8–15 digits in total (E.164). Without "+", 11–15 digits are
 *   accepted as an international number too (e.g. 447911123456).
 * - Obviously fake numbers (the same digit repeated) are rejected.
 * An empty optional field is fine.
 */
function phone_check(?string $raw, bool $required = true): array {
    $v = trim((string)$raw);
    if ($v === '') return $required ? ['ok'=>false, 'e164'=>'', 'error'=>'Please enter your mobile number.'] : ['ok'=>true, 'e164'=>'', 'error'=>''];
    if (preg_match('/[^\d\s\-().+]/', $v) || substr_count($v, '+') > 1 || (str_contains($v, '+') && $v[0] !== '+'))
        return ['ok'=>false, 'e164'=>'', 'error'=>'Please use digits only (spaces, dashes and a leading + are fine).'];
    $plus = $v[0] === '+';
    $d = preg_replace('/\D/', '', $v);
    $bad = ['ok'=>false, 'e164'=>'', 'error'=>'Please enter a valid mobile number, e.g. 98765 43210 or +44 7911 123456.'];
    $india = fn(string $ten) => preg_match('/^[6-9]\d{9}$/', $ten) && !preg_match('/^(\d)\1{9}$/', $ten)
        ? ['ok'=>true, 'e164'=>'+91'.$ten, 'error'=>''] : ['ok'=>false, 'e164'=>'', 'error'=>'Please enter a valid 10-digit Indian mobile number (starting with 6, 7, 8 or 9).'];
    if ($plus) {
        if (str_starts_with($d, '91')) return strlen($d) === 12 ? $india(substr($d, 2)) : $india('');
        if ($d === '' || $d[0] === '0' || strlen($d) < 8 || strlen($d) > 15 || preg_match('/^(\d)\1+$/', $d)) return $bad;
        return ['ok'=>true, 'e164'=>'+'.$d, 'error'=>''];
    }
    if (strlen($d) === 10) return $india($d);
    if (strlen($d) === 12 && str_starts_with($d, '91')) return $india(substr($d, 2));
    if (strlen($d) === 11 && $d[0] === '0') return preg_match('/^0[1-5]\d{9}$/', $d) ? ['ok'=>true, 'e164'=>'+91'.substr($d, 1), 'error'=>''] : $india(substr($d, 1));   // 0 + STD landline, or 0 + mobile
    if (strlen($d) >= 11 && strlen($d) <= 15 && $d[0] !== '0' && !preg_match('/^(\d)\1+$/', $d)) return ['ok'=>true, 'e164'=>'+'.$d, 'error'=>''];
    return strlen($d) < 10 ? ['ok'=>false, 'e164'=>'', 'error'=>'That number looks too short. Indian mobiles have 10 digits; for other countries add the country code with +.'] : $bad;
}

/** Normalised form for storing / matching (+919812345678); falls back to the old digits-and-plus cleaning. */
function phone_normalize(?string $raw): string {
    $r = phone_check($raw, false);
    return $r['ok'] && $r['e164'] !== '' ? $r['e164'] : preg_replace('/[^0-9+]/', '', (string)$raw);
}
