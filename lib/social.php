<?php
/**
 * Social publishing: Facebook Page, Instagram Business, LinkedIn, X (Twitter).
 */

function social_publish(array $post): array {
    $platforms = array_filter(array_map('trim', explode(',', $post['platforms'])));
    $results = json_decode($post['results'] ?: '{}', true) ?: [];
    $mediaUrl = $post['media'] ? base_url($post['media']) : '';
    foreach ($platforms as $pf) {
        if (!empty($results[$pf]['ok'])) continue;
        $acc = q_row("SELECT * FROM social_accounts WHERE platform=? AND active=1 ORDER BY id DESC LIMIT 1", [$pf]);
        if (!$acc) { $results[$pf] = ['ok'=>false,'error'=>'No connected '.$pf.' account']; continue; }
        $results[$pf] = match ($pf) {
            'facebook'  => social_fb_publish($acc, $post, $mediaUrl),
            'instagram' => social_ig_publish($acc, $post, $mediaUrl),
            'linkedin'  => social_li_publish($acc, $post, $mediaUrl),
            'x'         => social_x_publish($acc, $post),
            default     => ['ok'=>false,'error'=>'Unknown platform'],
        };
    }
    return $results;
}

function social_fb_publish(array $acc, array $post, string $mediaUrl): array {
    $page = $acc['account_ref']; $tok = $acc['access_token'];
    $msg = $post['content'] . ($post['link'] ? "\n".$post['link'] : '');
    if ($post['media_type'] === 'image' && $mediaUrl) {
        $r = http_request("https://graph.facebook.com/v19.0/$page/photos", 'POST', ['url'=>$mediaUrl,'caption'=>$msg,'access_token'=>$tok]);
    } elseif ($post['media_type'] === 'video' && $mediaUrl) {
        $r = http_request("https://graph.facebook.com/v19.0/$page/videos", 'POST', ['file_url'=>$mediaUrl,'description'=>$msg,'access_token'=>$tok]);
    } else {
        $r = http_request("https://graph.facebook.com/v19.0/$page/feed", 'POST', ['message'=>$msg,'access_token'=>$tok]);
    }
    $id = $r['json']['id'] ?? $r['json']['post_id'] ?? '';
    return $id ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>$r['json']['error']['message'] ?? 'HTTP '.$r['code']];
}

function social_ig_publish(array $acc, array $post, string $mediaUrl): array {
    if (!$mediaUrl) return ['ok'=>false,'error'=>'Instagram requires an image or video'];
    $ig = $acc['account_ref']; $tok = $acc['access_token'];
    $params = $post['media_type']==='video'
        ? ['media_type'=>'REELS','video_url'=>$mediaUrl,'caption'=>$post['content'],'access_token'=>$tok]
        : ['image_url'=>$mediaUrl,'caption'=>$post['content'],'access_token'=>$tok];
    $r = http_request("https://graph.facebook.com/v19.0/$ig/media", 'POST', $params);
    $creation = $r['json']['id'] ?? '';
    if (!$creation) return ['ok'=>false,'error'=>$r['json']['error']['message'] ?? 'container failed'];
    for ($i=0; $i<10; $i++) {
        $st = http_request("https://graph.facebook.com/v19.0/$creation?fields=status_code&access_token=$tok");
        if (($st['json']['status_code'] ?? 'FINISHED') === 'FINISHED') break;
        sleep(3);
    }
    $pub = http_request("https://graph.facebook.com/v19.0/$ig/media_publish", 'POST', ['creation_id'=>$creation,'access_token'=>$tok]);
    $id = $pub['json']['id'] ?? '';
    return $id ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>$pub['json']['error']['message'] ?? 'publish failed'];
}

function social_li_publish(array $acc, array $post, string $mediaUrl): array {
    $tok = $acc['access_token']; $author = $acc['account_ref'];
    $body = ['author'=>$author,'lifecycleState'=>'PUBLISHED',
      'specificContent'=>['com.linkedin.ugc.ShareContent'=>[
          'shareCommentary'=>['text'=>$post['content'].($post['link'] ? "\n".$post['link'] : '')],
          'shareMediaCategory'=>'NONE']],
      'visibility'=>['com.linkedin.ugc.MemberNetworkVisibility'=>'PUBLIC']];
    if ($post['media_type']==='image' && $mediaUrl && !empty($post['media'])) {
        $reg = http_request('https://api.linkedin.com/v2/assets?action=registerUpload','POST', json_encode([
          'registerUploadRequest'=>['recipes'=>['urn:li:digitalmediaRecipe:feedshare-image'],'owner'=>$author,
            'serviceRelationships'=>[['relationshipType'=>'OWNER','identifier'=>'urn:li:userGeneratedContent']]]]),
          ['Content-Type: application/json','Authorization: Bearer '.$tok]);
        $uploadUrl = $reg['json']['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? '';
        $asset = $reg['json']['value']['asset'] ?? '';
        if ($uploadUrl && is_file(ROOT_PATH.'/'.$post['media'])) {
            http_request($uploadUrl,'POST', file_get_contents(ROOT_PATH.'/'.$post['media']), ['Authorization: Bearer '.$tok,'Content-Type: application/octet-stream']);
            $body['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
            $body['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [['status'=>'READY','media'=>$asset]];
        }
    }
    $r = http_request('https://api.linkedin.com/v2/ugcPosts','POST', json_encode($body),
        ['Content-Type: application/json','Authorization: Bearer '.$tok,'X-Restli-Protocol-Version: 2.0.0']);
    $id = $r['json']['id'] ?? '';
    return $id ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>$r['json']['message'] ?? 'HTTP '.$r['code']];
}

function social_x_publish(array $acc, array $post): array {
    $extra = json_decode($acc['extra'] ?: '{}', true) ?: [];
    $ck = $extra['consumer_key'] ?? ''; $cs = $extra['consumer_secret'] ?? '';
    $tok = $acc['access_token']; $ts = $extra['token_secret'] ?? '';
    if (!$ck || !$cs || !$tok || !$ts) return ['ok'=>false,'error'=>'X needs consumer key/secret + access token/secret'];
    $url = 'https://api.twitter.com/2/tweets';
    $oauth = ['oauth_consumer_key'=>$ck,'oauth_nonce'=>bin2hex(random_bytes(16)),'oauth_signature_method'=>'HMAC-SHA1',
              'oauth_timestamp'=>(string)time(),'oauth_token'=>$tok,'oauth_version'=>'1.0'];
    ksort($oauth);
    $base = 'POST&'.rawurlencode($url).'&'.rawurlencode(http_build_query($oauth, '', '&', PHP_QUERY_RFC3986));
    $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $base, rawurlencode($cs).'&'.rawurlencode($ts), true));
    $header = 'OAuth '.implode(', ', array_map(fn($k)=>$k.'="'.rawurlencode($oauth[$k]).'"', array_keys($oauth)));
    $text = mb_substr($post['content'].($post['link'] ? ' '.$post['link'] : ''), 0, 280);
    $r = http_request($url, 'POST', json_encode(['text'=>$text]), ['Content-Type: application/json','Authorization: '.$header]);
    $id = $r['json']['data']['id'] ?? '';
    return $id ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>json_encode($r['json']['errors'] ?? $r['json']['detail'] ?? ('HTTP '.$r['code']))];
}
