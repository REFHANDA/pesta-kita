@include('errors.template', [
    'code' => 403,
    'title' => 'Anda tidak punya akses',
    'description' => 'Halaman ini bukan untuk peran akun Anda. Kembali ke beranda untuk melanjutkan.'
])