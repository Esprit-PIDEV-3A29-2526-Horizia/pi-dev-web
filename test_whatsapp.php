<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.twilio.com/2010-04-01/Accounts/AC20136e23acf49b39e9e1ba73c54b81a1/Messages.json');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, 'AC20136e23acf49b39e9e1ba73c54b81a1:3574da188e3c2784e717ccb7eccbdc68');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'From' => 'whatsapp:+14155238886',
    'To'   => 'whatsapp:+21694670088',
    'Body' => 'Test Horizia WhatsApp !'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$r = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "CURL ERROR: " . $err . "\n";
} else {
    $data = json_decode($r, true);
    echo "Status : " . ($data['status'] ?? 'unknown') . "\n";
    echo "SID    : " . ($data['sid'] ?? 'none') . "\n";
    echo "Error  : " . ($data['message'] ?? 'none') . "\n";
    echo "Code   : " . ($data['code'] ?? 'none') . "\n";
    echo "\nFull response:\n" . $r . "\n";
}