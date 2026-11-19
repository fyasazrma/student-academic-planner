const onReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
        return;
    }

    callback();
};

onReady(() => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (! input) {
            return;
        }

        const eyeIcon = button.querySelector('[data-eye-icon]');
        const eyeOffIcon = button.querySelector('[data-eye-off-icon]');

        button.addEventListener('click', () => {
            const isPassword = input.type === 'password';

            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');

            eyeIcon?.classList.toggle('hidden', isPassword);
            eyeOffIcon?.classList.toggle('hidden', ! isPassword);
        });
    });

    document.querySelectorAll('form[data-auth-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-submit-button]');
            const label = form.querySelector('[data-submit-label]');
            const spinner = form.querySelector('[data-submit-spinner]');
            const arrow = form.querySelector('[data-submit-arrow]');

            if (! button) {
                return;
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');

            if (label) {
                label.textContent = 'Memproses...';
            }

            spinner?.classList.remove('hidden');
            arrow?.classList.add('hidden');
        });
    });

    const drawer = document.getElementById('sidebar-drawer');
    const drawerOpenButton = document.querySelector('[data-sidebar-open]');

    const setDrawer = (open) => {
        if (! drawer) {
            return;
        }

        drawer.classList.toggle('hidden', ! open);
        drawer.classList.toggle('flex', open);
        drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
        drawerOpenButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    drawerOpenButton?.addEventListener('click', () => setDrawer(true));

    document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
        button.addEventListener('click', () => setDrawer(false));
    });

    const detailDialog = document.getElementById('agenda-detail');
    const eventsData = document.getElementById('agenda-events-data');
    const timelineData = document.getElementById('timeline-events-data');
    const addAgendaEvent = (carry, event) => {
        if (! event || ! event.id) {
            return carry;
        }

        carry[event.id] = event;

        return carry;
    };
    let agendaEvents = {};

    if (eventsData) {
        try {
            JSON.parse(eventsData.textContent || '[]').reduce(addAgendaEvent, agendaEvents);
        } catch (error) {
            agendaEvents = {};
        }
    }

    if (timelineData) {
        try {
            const timeline = JSON.parse(timelineData.textContent || '{}');

            (timeline.bars || []).forEach((bar) => addAgendaEvent(agendaEvents, {
                id: `tugas-${bar.id}-deadline`,
                label: 'Deadline tugas',
                judul: bar.judul,
                mata_kuliah: bar.mata_kuliah,
                tanggal_label: bar.deadline_label,
                waktu_label: bar.deadline_label,
                awal_label: bar.dipublikasikan_label,
                akhir_label: bar.deadline_label,
                awal_label_name: 'Tanggal dipublikasikan',
                akhir_label_name: 'Deadline',
                publikasi_label: bar.dipublikasikan_label,
                deskripsi: bar.deskripsi,
                status_label: bar.status_label,
                lokasi: null,
            }));

            (timeline.markers || []).forEach((marker) => addAgendaEvent(agendaEvents, {
                id: marker.id,
                label: 'Ujian',
                judul: marker.judul,
                mata_kuliah: marker.mata_kuliah,
                tanggal_label: marker.tanggal_label,
                waktu_label: marker.waktu_label || 'Tidak tersedia',
                awal_label: marker.tanggal_label,
                akhir_label: marker.tanggal_label,
                awal_label_name: 'Tanggal ujian',
                akhir_label_name: 'Tanggal ujian',
                publikasi_label: 'Tidak tersedia',
                deskripsi: null,
                status_label: null,
                lokasi: marker.lokasi,
            }));
        } catch (error) {
            // Timeline detail tetap dapat dibuka dari data kalender bila tersedia.
        }
    }

    const detailFields = {
        label: document.getElementById('agenda-detail-label'),
        title: document.getElementById('agenda-detail-title'),
        course: document.getElementById('agenda-detail-course'),
        date: document.getElementById('agenda-detail-date'),
        time: document.getElementById('agenda-detail-time'),
        startLabel: document.getElementById('agenda-detail-start-label'),
        start: document.getElementById('agenda-detail-start'),
        endLabel: document.getElementById('agenda-detail-end-label'),
        end: document.getElementById('agenda-detail-end'),
        published: document.getElementById('agenda-detail-published'),
        location: document.getElementById('agenda-detail-location'),
        status: document.getElementById('agenda-detail-status'),
        description: document.getElementById('agenda-detail-description'),
    };

    const openDetail = (id) => {
        const event = agendaEvents[id];

        if (! event || ! detailDialog) {
            return;
        }

        detailFields.label.textContent = event.label || 'Agenda';
        detailFields.title.textContent = event.judul || 'Detail agenda';
        detailFields.course.textContent = event.mata_kuliah || 'Tidak tersedia';
        detailFields.date.textContent = event.tanggal_label || 'Tidak tersedia';
        detailFields.time.textContent = event.waktu_label || 'Tidak tersedia';
        detailFields.startLabel.textContent = event.awal_label_name || 'Tanggal mulai';
        detailFields.start.textContent = event.awal_label || 'Tidak tersedia';
        detailFields.endLabel.textContent = event.akhir_label_name || 'Tanggal selesai';
        detailFields.end.textContent = event.akhir_label || 'Tidak tersedia';
        detailFields.published.textContent = event.publikasi_label || 'Tidak tersedia';
        detailFields.location.textContent = event.lokasi || 'Tidak tersedia';
        detailFields.status.textContent = event.status_label || 'Tidak tersedia';
        detailFields.description.textContent = event.deskripsi || 'Tidak tersedia';

        if (typeof detailDialog.showModal === 'function') {
            detailDialog.showModal();
        } else {
            detailDialog.setAttribute('open', '');
        }
    };

    document.querySelectorAll('[data-agenda-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => openDetail(trigger.dataset.agendaTrigger));
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openDetail(trigger.dataset.agendaTrigger);
            }
        });
    });

    document.querySelectorAll('[data-agenda-close]').forEach((button) => {
        button.addEventListener('click', () => {
            if (typeof detailDialog?.close === 'function') {
                detailDialog.close();
            } else {
                detailDialog?.removeAttribute('open');
            }
        });
    });

    detailDialog?.addEventListener('click', (event) => {
        if (event.target === detailDialog) {
            detailDialog.close();
        }
    });

    const gabungDialog = document.getElementById('dialog-gabung');
    const gabungLabel = gabungDialog?.querySelector('[data-gabung-label]');

    const openGabungDialog = (label) => {
        if (! gabungDialog) {
            return;
        }

        if (gabungLabel && label) {
            gabungLabel.textContent = label;
        }

        if (typeof gabungDialog.showModal === 'function') {
            gabungDialog.showModal();
        } else {
            gabungDialog.setAttribute('open', '');
        }
    };

    const closeGabungDialog = () => {
        if (! gabungDialog) {
            return;
        }

        if (typeof gabungDialog.close === 'function') {
            gabungDialog.close();
        } else {
            gabungDialog.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-gabung-trigger]').forEach((button) => {
        button.addEventListener('click', () => openGabungDialog(button.dataset.gabungLabel));
    });

    gabungDialog?.querySelectorAll('[data-gabung-close]').forEach((button) => {
        button.addEventListener('click', closeGabungDialog);
    });

    gabungDialog?.addEventListener('click', (event) => {
        if (event.target === gabungDialog) {
            closeGabungDialog();
        }
    });

    // Buka kembali dialog otomatis saat validasi kunci gabung gagal.
    if (gabungDialog?.hasAttribute('data-gabung-error')) {
        openGabungDialog();
    }

    const mkConfirmDialog = document.getElementById('dialog-konfirmasi-mk');
    const mkConfirmForm = document.getElementById('form-konfirmasi-mk');
    const mkConfirmFields = {
        judul: mkConfirmDialog?.querySelector('[data-mk-dialog-judul]'),
        pesan: mkConfirmDialog?.querySelector('[data-mk-dialog-pesan]'),
        label: mkConfirmDialog?.querySelector('[data-mk-dialog-label]'),
        submit: mkConfirmDialog?.querySelector('[data-mk-dialog-submit]'),
        method: mkConfirmForm?.querySelector('[data-mk-method-input]'),
        status: mkConfirmForm?.querySelector('[data-mk-status-input]'),
    };

    const closeMkConfirmDialog = () => {
        if (typeof mkConfirmDialog?.close === 'function') {
            mkConfirmDialog.close();
        } else {
            mkConfirmDialog?.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-mk-konfirmasi]').forEach((button) => {
        button.addEventListener('click', () => {
            if (! mkConfirmDialog || ! mkConfirmForm) {
                return;
            }

            mkConfirmForm.action = button.dataset.mkAction || '';
            if (mkConfirmFields.method) {
                mkConfirmFields.method.value = button.dataset.mkMethod || 'POST';
            }
            if (mkConfirmFields.status) {
                mkConfirmFields.status.value = button.dataset.mkStatus || '1';
            }
            if (mkConfirmFields.judul) {
                mkConfirmFields.judul.textContent = button.dataset.mkJudul || 'Konfirmasi aksi';
            }
            if (mkConfirmFields.pesan) {
                mkConfirmFields.pesan.textContent = button.dataset.mkPesan || 'Konfirmasi aksi pada mata kuliah terpilih.';
            }
            if (mkConfirmFields.label) {
                mkConfirmFields.label.textContent = button.dataset.mkLabel || 'Mata kuliah terpilih';
            }
            if (mkConfirmFields.submit) {
                mkConfirmFields.submit.textContent = button.dataset.mkSubmit || 'Konfirmasi';
            }

            if (typeof mkConfirmDialog.showModal === 'function') {
                mkConfirmDialog.showModal();
            } else {
                mkConfirmDialog.setAttribute('open', '');
            }
        });
    });

    mkConfirmDialog?.querySelectorAll('[data-mk-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeMkConfirmDialog);
    });

    mkConfirmDialog?.addEventListener('click', (event) => {
        if (event.target === mkConfirmDialog) {
            closeMkConfirmDialog();
        }
    });

    const taConfirmDialog = document.getElementById('dialog-konfirmasi-ta');
    const taConfirmForm = document.getElementById('form-konfirmasi-ta');
    const taConfirmFields = {
        judul: taConfirmDialog?.querySelector('[data-ta-dialog-judul]'),
        pesan: taConfirmDialog?.querySelector('[data-ta-dialog-pesan]'),
        label: taConfirmDialog?.querySelector('[data-ta-dialog-label]'),
        submit: taConfirmDialog?.querySelector('[data-ta-dialog-submit]'),
        method: taConfirmForm?.querySelector('[data-ta-method-input]'),
        status: taConfirmForm?.querySelector('[data-ta-status-input]'),
    };

    const closeTaConfirmDialog = () => {
        if (typeof taConfirmDialog?.close === 'function') {
            taConfirmDialog.close();
        } else {
            taConfirmDialog?.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-ta-konfirmasi]').forEach((button) => {
        button.addEventListener('click', () => {
            if (! taConfirmDialog || ! taConfirmForm) {
                return;
            }

            taConfirmForm.action = button.dataset.taAction || '';
            if (taConfirmFields.method) {
                taConfirmFields.method.value = button.dataset.taMethod || 'POST';
            }
            if (taConfirmFields.status) {
                taConfirmFields.status.value = button.dataset.taStatus || '1';
            }
            if (taConfirmFields.judul) {
                taConfirmFields.judul.textContent = button.dataset.taJudul || 'Konfirmasi aksi';
            }
            if (taConfirmFields.pesan) {
                taConfirmFields.pesan.textContent = button.dataset.taPesan || 'Konfirmasi aksi pada tahun akademik terpilih.';
            }
            if (taConfirmFields.label) {
                taConfirmFields.label.textContent = button.dataset.taLabel || 'Tahun akademik terpilih';
            }
            if (taConfirmFields.submit) {
                taConfirmFields.submit.textContent = button.dataset.taSubmit || 'Konfirmasi';
            }

            if (typeof taConfirmDialog.showModal === 'function') {
                taConfirmDialog.showModal();
            } else {
                taConfirmDialog.setAttribute('open', '');
            }
        });
    });

    taConfirmDialog?.querySelectorAll('[data-ta-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeTaConfirmDialog);
    });

    taConfirmDialog?.addEventListener('click', (event) => {
        if (event.target === taConfirmDialog) {
            closeTaConfirmDialog();
        }
    });

    const dosenConfirmDialog = document.getElementById('dialog-konfirmasi-dosen');
    const dosenConfirmForm = document.getElementById('form-konfirmasi-dosen');
    const dosenConfirmFields = {
        judul: dosenConfirmDialog?.querySelector('[data-dosen-dialog-judul]'),
        pesan: dosenConfirmDialog?.querySelector('[data-dosen-dialog-pesan]'),
        label: dosenConfirmDialog?.querySelector('[data-dosen-dialog-label]'),
        submit: dosenConfirmDialog?.querySelector('[data-dosen-dialog-submit]'),
        status: dosenConfirmForm?.querySelector('[data-dosen-status-input]'),
        method: dosenConfirmForm?.querySelector('[data-dosen-method-input]'),
    };

    const closeDosenConfirmDialog = () => {
        if (typeof dosenConfirmDialog?.close === 'function') {
            dosenConfirmDialog.close();
        } else {
            dosenConfirmDialog?.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-dosen-konfirmasi]').forEach((button) => {
        button.addEventListener('click', () => {
            if (! dosenConfirmDialog || ! dosenConfirmForm) {
                return;
            }

            dosenConfirmForm.action = button.dataset.dosenAction || '';

            if (dosenConfirmFields.status) {
                const status = button.dataset.dosenStatus || '';

                dosenConfirmFields.status.value = status;
                dosenConfirmFields.status.disabled = status === '';
            }

            if (dosenConfirmFields.method) {
                const method = button.dataset.dosenMethod || '';

                dosenConfirmFields.method.value = method;
                dosenConfirmFields.method.disabled = method === '';
            }

            if (dosenConfirmFields.judul) {
                dosenConfirmFields.judul.textContent = button.dataset.dosenJudul || 'Konfirmasi aksi';
            }
            if (dosenConfirmFields.pesan) {
                dosenConfirmFields.pesan.textContent = button.dataset.dosenPesan || 'Konfirmasi aksi pada kelas terpilih.';
            }
            if (dosenConfirmFields.label) {
                dosenConfirmFields.label.textContent = button.dataset.dosenLabel || 'Kelas terpilih';
            }
            if (dosenConfirmFields.submit) {
                dosenConfirmFields.submit.textContent = button.dataset.dosenSubmit || 'Konfirmasi';
            }

            if (typeof dosenConfirmDialog.showModal === 'function') {
                dosenConfirmDialog.showModal();
            } else {
                dosenConfirmDialog.setAttribute('open', '');
            }
        });
    });

    dosenConfirmDialog?.querySelectorAll('[data-dosen-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeDosenConfirmDialog);
    });

    dosenConfirmDialog?.addEventListener('click', (event) => {
        if (event.target === dosenConfirmDialog) {
            closeDosenConfirmDialog();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        setDrawer(false);
    });
});
