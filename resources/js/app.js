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

    const roleSelect = document.querySelector('[data-role-select]');

    const updateRoleFields = () => {
        if (! roleSelect) {
            return;
        }

        document.querySelectorAll('[data-role-field]').forEach((field) => {
            field.classList.toggle('hidden', field.dataset.roleField !== roleSelect.value);
        });
    };

    roleSelect?.addEventListener('change', updateRoleFields);
    updateRoleFields();

    const userConfirmDialog = document.getElementById('dialog-konfirmasi-pengguna');
    const userConfirmForm = document.getElementById('form-konfirmasi-pengguna');
    const userConfirmFields = {
        judul: userConfirmDialog?.querySelector('[data-user-dialog-judul]'),
        pesan: userConfirmDialog?.querySelector('[data-user-dialog-pesan]'),
        label: userConfirmDialog?.querySelector('[data-user-dialog-label]'),
        submit: userConfirmDialog?.querySelector('[data-user-dialog-submit]'),
        status: userConfirmForm?.querySelector('[data-user-status-input]'),
    };

    const closeUserConfirmDialog = () => {
        if (typeof userConfirmDialog?.close === 'function') {
            userConfirmDialog.close();
        } else {
            userConfirmDialog?.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-user-konfirmasi]').forEach((button) => {
        button.addEventListener('click', () => {
            if (! userConfirmDialog || ! userConfirmForm) {
                return;
            }

            userConfirmForm.action = button.dataset.userAction || '';
            if (userConfirmFields.status) {
                userConfirmFields.status.value = button.dataset.userStatus || '1';
            }
            if (userConfirmFields.judul) {
                userConfirmFields.judul.textContent = button.dataset.userJudul || 'Konfirmasi aksi';
            }
            if (userConfirmFields.pesan) {
                userConfirmFields.pesan.textContent = button.dataset.userPesan || 'Konfirmasi perubahan status akun.';
            }
            if (userConfirmFields.label) {
                userConfirmFields.label.textContent = button.dataset.userLabel || '';
            }
            if (userConfirmFields.submit) {
                userConfirmFields.submit.textContent = button.dataset.userSubmit || 'Konfirmasi';
            }

            if (typeof userConfirmDialog.showModal === 'function') {
                userConfirmDialog.showModal();
            } else {
                userConfirmDialog.setAttribute('open', '');
            }
        });
    });

    userConfirmDialog?.querySelectorAll('[data-user-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeUserConfirmDialog);
    });

    userConfirmDialog?.addEventListener('click', (event) => {
        if (event.target === userConfirmDialog) {
            closeUserConfirmDialog();
        }
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
    let agendaEvents = {};

    if (eventsData) {
        try {
            const parsed = JSON.parse(eventsData.textContent || '{}');

            if (Array.isArray(parsed)) {
                parsed.forEach((event) => {
                    if (event && event.id) {
                        agendaEvents[event.id] = event;
                    }
                });
            } else if (parsed && typeof parsed === 'object') {
                agendaEvents = parsed;
            }
        } catch (error) {
            agendaEvents = {};
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

    const jadwalConfirmDialog = document.getElementById('dialog-konfirmasi-jadwal');
    const jadwalConfirmForm = document.getElementById('form-konfirmasi-jadwal');
    const jadwalConfirmFields = {
        judul: jadwalConfirmDialog?.querySelector('[data-jadwal-dialog-judul]'),
        pesan: jadwalConfirmDialog?.querySelector('[data-jadwal-dialog-pesan]'),
        label: jadwalConfirmDialog?.querySelector('[data-jadwal-dialog-label]'),
        submit: jadwalConfirmDialog?.querySelector('[data-jadwal-dialog-submit]'),
        method: jadwalConfirmForm?.querySelector('[data-jadwal-method-input]'),
    };

    const closeJadwalConfirmDialog = () => {
        if (typeof jadwalConfirmDialog?.close === 'function') {
            jadwalConfirmDialog.close();
        } else {
            jadwalConfirmDialog?.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-jadwal-konfirmasi]').forEach((button) => {
        button.addEventListener('click', () => {
            if (! jadwalConfirmDialog || ! jadwalConfirmForm) {
                return;
            }

            jadwalConfirmForm.action = button.dataset.jadwalAction || '';
            if (jadwalConfirmFields.method) {
                jadwalConfirmFields.method.value = button.dataset.jadwalMethod || 'DELETE';
            }
            if (jadwalConfirmFields.judul) {
                jadwalConfirmFields.judul.textContent = button.dataset.jadwalJudul || 'Konfirmasi aksi';
            }
            if (jadwalConfirmFields.pesan) {
                jadwalConfirmFields.pesan.textContent = button.dataset.jadwalPesan || 'Konfirmasi aksi pada jadwal terpilih.';
            }
            if (jadwalConfirmFields.label) {
                jadwalConfirmFields.label.textContent = button.dataset.jadwalLabel || 'Jadwal terpilih';
            }
            if (jadwalConfirmFields.submit) {
                jadwalConfirmFields.submit.textContent = button.dataset.jadwalSubmit || 'Hapus';
            }

            if (typeof jadwalConfirmDialog.showModal === 'function') {
                jadwalConfirmDialog.showModal();
            } else {
                jadwalConfirmDialog.setAttribute('open', '');
            }
        });
    });

    jadwalConfirmDialog?.querySelectorAll('[data-jadwal-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeJadwalConfirmDialog);
    });

    jadwalConfirmDialog?.addEventListener('click', (event) => {
        if (event.target === jadwalConfirmDialog) {
            closeJadwalConfirmDialog();
        }
    });

    const materiDialog = document.getElementById('dialog-detail-materi');
    const materiFields = {
        judul: materiDialog?.querySelector('[data-materi-field="judul"]'),
        deskripsi: materiDialog?.querySelector('[data-materi-field="deskripsi"]'),
        dipublikasikan: materiDialog?.querySelector('[data-materi-field="dipublikasikan_label"]'),
        fileName: materiDialog?.querySelector('[data-materi-field="file_name"]'),
        fileSize: materiDialog?.querySelector('[data-materi-field="file_size_label"]'),
        fileMime: materiDialog?.querySelector('[data-materi-field="file_mime"]'),
        dibuatOleh: materiDialog?.querySelector('[data-materi-field="dibuat_oleh"]'),
        download: materiDialog?.querySelector('[data-materi-action="download"]'),
        edit: materiDialog?.querySelector('[data-materi-action="edit"]'),
        statusDraft: materiDialog?.querySelector('[data-materi-status-wrap="draft"]'),
        statusPublik: materiDialog?.querySelector('[data-materi-status-wrap="dipublikasikan"]'),
    };

    const isiFieldMateri = (element, nilai, fallback = '-') => {
        if (element) {
            element.textContent = nilai || fallback;
        }
    };

    const tutupMateriDialog = () => {
        if (typeof materiDialog?.close === 'function') {
            materiDialog.close();
        } else {
            materiDialog?.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-materi-detail-trigger]').forEach((button) => {
        button.addEventListener('click', () => {
            if (! materiDialog) {
                return;
            }

            let materi;

            try {
                materi = JSON.parse(button.dataset.materiDetail || '{}');
            } catch (error) {
                return;
            }

            isiFieldMateri(materiFields.judul, materi.judul, 'Judul materi');
            isiFieldMateri(materiFields.deskripsi, materi.deskripsi, 'Tidak ada deskripsi.');
            isiFieldMateri(materiFields.dipublikasikan, materi.dipublikasikan_label, 'Belum dipublikasikan');
            isiFieldMateri(materiFields.fileName, materi.file_name);
            isiFieldMateri(materiFields.fileSize, materi.file_size_label);
            isiFieldMateri(materiFields.fileMime, materi.file_mime);
            isiFieldMateri(materiFields.dibuatOleh, materi.dibuat_oleh);

            materiFields.statusDraft?.classList.toggle('hidden', materi.status !== 'draft');
            materiFields.statusPublik?.classList.toggle('hidden', materi.status !== 'dipublikasikan');

            if (materiFields.download) {
                materiFields.download.href = button.dataset.materiDownload || materiFields.download.href;
            }

            if (materiFields.edit) {
                materiFields.edit.href = button.dataset.materiEdit || materiFields.edit.href;
            }

            if (typeof materiDialog.showModal === 'function') {
                materiDialog.showModal();
            } else {
                materiDialog.setAttribute('open', '');
            }
        });
    });

    materiDialog?.querySelectorAll('[data-materi-detail-close]').forEach((button) => {
        button.addEventListener('click', tutupMateriDialog);
    });

    materiDialog?.addEventListener('click', (event) => {
        if (event.target === materiDialog) {
            tutupMateriDialog();
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

/**
 * Sistem motion landing page.
 *
 * Berjalan hanya ketika elemen [data-landing] tersedia. Konten tetap terlihat
 * tanpa JavaScript, ketika IntersectionObserver tidak ada, dan ketika
 * prefers-reduced-motion aktif: status motion hanya diaktifkan dari sini.
 */
const initLandingMotion = () => {
    const root = document.querySelector('[data-landing]');

    if (! root || typeof window.IntersectionObserver !== 'function') {
        return;
    }

    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const revealTargets = Array.from(root.querySelectorAll('[data-reveal], [data-reveal-item]'));

    if (! revealTargets.length) {
        return;
    }

    root.querySelectorAll('[data-reveal-group]').forEach((group) => {
        const items = Array.from(group.children).filter((child) => child.matches('[data-reveal], [data-reveal-item]'));

        items.forEach((item, index) => {
            if (index > 0) {
                item.style.setProperty('--landing-delay', `${Math.min(index, 5) * 80}ms`);
            }
        });
    });

    let observer;

    const markRevealed = (element) => {
        element.classList.add('is-revealed');
        observer?.unobserve(element);
    };

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    markRevealed(entry.target);
                }
            });
        },
        { threshold: 0.08, rootMargin: '0px 0px -32px 0px' },
    );

    // Status motion dipasang paling akhir: bila inisialisasi gagal, tidak ada
    // konten yang disembunyikan.
    root.setAttribute('data-motion-ready', '');

    revealTargets.forEach((element) => {
        const rect = element.getBoundingClientRect();

        if (rect.top < window.innerHeight * 0.92 && rect.bottom > 0) {
            markRevealed(element);
            return;
        }

        observer.observe(element);
    });

    const progressBar = root.querySelector('[data-progress]');
    const parallaxItems = Array.from(root.querySelectorAll('[data-parallax]'));
    const canParallax = window.innerWidth >= 768 && parallaxItems.length > 0;

    if (! progressBar && ! canParallax) {
        return;
    }

    let frame = 0;

    const updateScrollMotion = () => {
        frame = 0;

        if (progressBar) {
            const scrollable = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
            const ratio = scrollable > 0 ? Math.min(1, Math.max(0, window.scrollY / scrollable)) : 0;

            progressBar.style.transform = `scaleX(${ratio.toFixed(4)})`;
        }

        if (! canParallax) {
            return;
        }

        const viewportHeight = window.innerHeight;

        parallaxItems.forEach((item) => {
            const rect = item.getBoundingClientRect();

            if (rect.bottom < -120 || rect.top > viewportHeight + 120) {
                return;
            }

            const amplitude = Number.parseFloat(item.dataset.parallax) || 10;
            const position = (rect.top + rect.height / 2 - viewportHeight / 2) / viewportHeight;
            const shift = Math.max(-1, Math.min(1, position)) * amplitude;

            item.style.setProperty('--landing-parallax-y', `${shift.toFixed(1)}px`);
        });
    };

    const requestScrollUpdate = () => {
        if (frame) {
            return;
        }

        frame = window.requestAnimationFrame(updateScrollMotion);
    };

    window.addEventListener('scroll', requestScrollUpdate, { passive: true });
    window.addEventListener('resize', requestScrollUpdate, { passive: true });
    updateScrollMotion();
};

onReady(initLandingMotion);
