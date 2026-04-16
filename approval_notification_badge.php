<?php
// helpers/hitung_notif_approval.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'koneksi.php';

$namaUser = $_SESSION['nama'];
$totalApproval = 0;

/**
 * Konfigurasi tabel BA
 * Mudah ditambah untuk BA lain
 */
$daftarBA = [
    [
        'tabel' => 'berita_acara_kerusakan',
        'map' => [
            'pembuat'           => 'approval_1',
            'penyetujui'        => 'approval_2',
            'peminjam'          => 'approval_3',
            'atasan_peminjam'   => 'approval_4',
            'diketahui'         => 'approval_5',
        ]
    ],
    [
        'tabel' => 'berita_acara_mutasi',
        'map'   => [
            'pengirim1'         => 'approval_1',
            'pengirim2'         => 'approval_2',
            'hrd_ga_pengirim'   => 'approval_3',
            'penerima1'         => 'approval_4',
            'penerima2'         => 'approval_5',
            'hrd_ga_penerima'   => 'approval_6',
            'diketahui'         => 'approval_7',
            'pemeriksa1'        => 'approval_8',
            'pemeriksa2'        => 'approval_9',
            'penyetujui1'       => 'approval_10',
            'penyetujui2'       => 'approval_11'
        ]
    ],
    [
        'tabel' => 'berita_acara_pengembalian_v2',
        'map'   => [
            'pengembali'   => 'approval_1',
            'penerima'     => 'approval_2',
            'diketahui'    => 'approval_3'
        ]
    ],
    [
        'tabel' => 'ba_serah_terima_asset',
        'map'   => [
            'peminjam'          => 'approval_1',
            'saksi'             => 'approval_2',
            'diketahui'         => 'approval_3',
            'pihak_pertama'     => 'approval_4'
        ]
    ],
    // [
    //     'tabel' => 'berita_acara_pemutihan',
    //     'map'   => [
    //         'pembuat'           => 'approval_1',
    //             'pembuat_site'      => 'approval_1',
    //         'pemeriksa'         => 'approval_2',
    //             'pemeriksa_site'    => 'approval_2',
    //         'diketahui1'        => 'approval_3',
    //             'diketahui1_site'   => 'approval_3',
    //         'diketahui2'        => 'approval_4',
    //             'disetujui1_site'   => 'approval_7',
    //         'diketahui3'        => 'approval_5',
    //             'diketahui2_site'   => 'approval_9',
    //             'diperiksa_site'    => 'approval_10',
    //         'dibukukan'         => 'approval_6',
    //         'disetujui1'        => 'approval_7',
    //         'disetujui2'        => 'approval_8',
    //         'disetujui3'        => 'approval_9',
    //             'mengetahui_site'   => 'approval_11'
    //     ]
    // ],

];

foreach ($daftarBA as $ba) {

    $tabel = $ba['tabel'];
    $map   = $ba['map'];

    $sql = "SELECT * FROM {$tabel} WHERE dihapus = 0";
    $res = mysqli_query($koneksi, $sql);

    while ($row = mysqli_fetch_assoc($res)) {

        foreach ($map as $kolomNama => $kolomApproval) {

            if (
                isset($row[$kolomNama], $row[$kolomApproval]) &&
                $row[$kolomNama] === $namaUser &&
                (int)$row[$kolomApproval] === 0
            ) {
                $totalApproval++;
            }

        }

    }
}

return $totalApproval;
