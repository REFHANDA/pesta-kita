@include('errors.template', [
    'code' => 429,
    'title' => 'Terlalu banyak percobaan',
    'description' => 'Tunggu beberapa saat, lalu coba lagi.'
])