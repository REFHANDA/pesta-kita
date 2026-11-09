@include('errors.template', [
    'code' => 500,
    'title' => 'Terjadi kesalahan di server',
    'description' => 'Ini bukan salah Anda. Muat ulang beberapa saat lagi. Jika terus terjadi, hubungi Admin.'
])