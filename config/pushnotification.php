<?php

return [
  'gcm' => [
      'priority' => 'normal',
      'dry_run' => true,
      'apiKey' => '738138744271',
  ],
  'fcm' => [
        'priority' => 'normal',
        'dry_run' => true,
        'apiKey' => 'AIzaSyBodLTR4r3Pmx_utbbLEtrxxcOvpMfVOps',
  ],
  'apn' => [
      'certificate' => __DIR__ . '/iosCertificates/education.pem',
      'passPhrase' => 'Download@123', //Optional
      //'passFile' => __DIR__ . '/iosCertificates/yourKey.pem', //Optional
      'dry_run' => true
  ]
];

