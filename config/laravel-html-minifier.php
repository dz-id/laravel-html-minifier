<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Aktifkan Minifier
    |--------------------------------------------------------------------------
    |
    | Setel bagian ini ke false untuk mematikan minify
    | 
    */
    "enable" => env("LARAVEL_HTML_MINIFIER_ENABLE", true),

    /*
    |--------------------------------------------------------------------------
    | Otomatis menambahkan semicolon diakhir kode untuk CSS
    |--------------------------------------------------------------------------
    |
    | Silahkan setel bidang ini ke false
    | jika kode CSS anda mengalami bug atau tidak berfungi
    | setelah menggunakan MinifyCss::class
    |
    */
    "css_automatic_insert_semicolon" => env("LARAVEL_HTML_MINIFIER_CSS_AUTOMATIC_INSERT_SEMICOLON", true),

    /*
    |--------------------------------------------------------------------------
    | Otomatis menambahkan semicolon diakhir kode untuk Javascript
    |--------------------------------------------------------------------------
    |
    | Silahkan setel bidang ini ke false
    | jika kode Javascript anda mengalami bug atau tidak berfungi
    | setelah menggunakan MinifyJavascript::class
    |
    | Dan jangan lupa untuk selalu mengakhiri dengan semicolon
    | jika bidang ini disetel ke false.
    */
    "js_automatic_insert_semicolon" => env("LARAVEL_HTML_MINIFIER_JS_AUTOMATIC_INSERT_SEMICOLON", true),

    /*
    |--------------------------------------------------------------------------
    | Aktifkan penghapusan komentar pada HTML
    |--------------------------------------------------------------------------
    |
    | Setel bagian ini ke false jika komentar HTML tidak ingin dihapus
    | 
    | Catatan: Pengaturan ini akan berlaku jika menggunakan Middleware MinifyHtml::class
    |
    */
    "remove_comments" => env("LARAVEL_HTML_MINIFIER_REMOVE_COMMENTS", true),

    /*
    |--------------------------------------------------------------------------
    | Kaburkan Kode Javascript
    |--------------------------------------------------------------------------
    |
    | Pengaturan ini akan berlaku jika menggunakan MinifyJavascript::class
    | 
    | Jika di setel ke true maka kode javascript akan dikaburkan,
    | Akan mentranslate kode js ke ord() fungsi php
    | Dan akan didecode dengan String.fromCharCode() Fungsi javascript
    | 
    | Catatan: Mungkin fungsi ini akan membuat kode Anda menjadi panjang.
    |
    */
    "obfuscate_javascript" => env("LARAVEL_HTML_MINIFIER_OBFUSCATE_JS", false),

    /*
    |--------------------------------------------------------------------------
    | Abaikan Routes
    |--------------------------------------------------------------------------
    |
    | Dalam pengecekan akan menggunakan fungsi request()->is(),
    | Dan jika route cocok maka akan diabaikan / Tidak diminify.
    | Anda dapat menggunakan * sebagai wildcard.
    |
    */
 
    "ignore" => [
     //   "*/download/*",  // Abaikan semua route yang mengandung download
     //   "admin/*",      // Abaikan semua route dengan awalan admin,
     //   "*/user"       // Abaikan route dengan akhiran user
    ],
];
