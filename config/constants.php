<?php

/**
 *@ShortDescription Define all constants that going to use in the Application.
 *
 * @var Array
 */
return [
     // Sercurity keys
    'ENCRYPTION_KEY1'     =>    env('EDUCATION_ENCRYPTION_KEY1'),
    'ENCRYPTION_KEY2'     =>    env('EDUCATION_ENCRYPTION_KEY2'),
    'ENVIRONMENT' 	  =>    "local",
    'FILEPREFIX'	  =>    3,
    'FILEPERMISSION'      =>    0755,
    'URLEXPIRY'		  =>    5, // in minutes 
    'API_PREFIX'	  =>    'api',
    'WEB_PREFIX'	  =>    'web',
    'MAIL_FROM'	  =>    'admin@education.com',
    //Media Path
    'PARENTS_PHOTO_PATH'     =>    '../storage/user_picture/',
    'STUDENTS_PHOTO_PATH'     =>    '../storage/student_picture/',
    'CSV_PATH'     =>    '../storage/csv/',
    'CRYPT_KEY'    => '$2y$10$Kf6kFKti0hNMNc7s8T0kkOd/z9kLg0I0bLuvVDBs016q.5IshZ6Um',
    'OTP_EMAILS' => ['parihar.toshik@fxbytes.com', 'gupta.kratika@fxbytes.com', 'bhawsar.romil@fxbytes.com']
];