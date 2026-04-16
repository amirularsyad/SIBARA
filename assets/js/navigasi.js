const quickAccess = document.querySelector('.quick-access');
const navigasiBar = document.querySelector('.navigasibar');
const buttons = document.querySelectorAll('.dokumenbtn');
const table = document.querySelector('table.dokumen');

// Toggle navigasiBar
quickAccess.onclick = function (e) {
    e.stopPropagation();
    navigasiBar.classList.toggle('active');

    // Jika navigasiBar diaktifkan, pastikan tabel nonaktif
    if (navigasiBar.classList.contains('active')) {
        table?.classList.remove('tabelaktif');
        buttons.forEach(button => {
            button.classList.remove('btnaktif');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('bi-folder2-open');
                icon.classList.add('bi-folder2');
            }
        });
    }
};

// Toggle table dokumen
buttons.forEach(button => {
    button.addEventListener('click', function (e) {
        e.stopPropagation();
        const icon = button.querySelector('i');

        const tableActive = table?.classList.contains('tabelaktif');

        if (!tableActive) {
            // Aktifkan tabel, matikan navigasi
            icon?.classList.remove('bi-folder2');
            icon?.classList.add('bi-folder2-open');
            table?.classList.add('tabelaktif');
            button.classList.add('btnaktif');

            navigasiBar?.classList.remove('active');
        } else {
            // Nonaktifkan tabel
            icon?.classList.remove('bi-folder2-open');
            icon?.classList.add('bi-folder2');
            table?.classList.remove('tabelaktif');
            button.classList.remove('btnaktif');
        }
    });
});

// Klik di luar untuk menutup semuanya
document.addEventListener('click', function (e) {
    const clickInsideNavigasi = navigasiBar?.contains(e.target);
    const clickInsideQuickAccess = quickAccess?.contains(e.target);
    const clickInsideTable = table?.contains(e.target);
    const clickOnButton = Array.from(buttons).some(btn => btn.contains(e.target));

    // Jika klik di luar navigasiBar dan quickAccess
    if (!clickInsideNavigasi && !clickInsideQuickAccess) {
        navigasiBar?.classList.remove('active');
    }

    // Jika klik di luar table dan tombol
    if (!clickInsideTable && !clickOnButton) {
        table?.classList.remove('tabelaktif');
        buttons.forEach(button => {
            button.classList.remove('btnaktif');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('bi-folder2-open');
                icon.classList.add('bi-folder2');
            }
        });
    }
});