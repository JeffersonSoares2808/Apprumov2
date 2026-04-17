document.addEventListener('DOMContentLoaded', () => {
    const slugify = (value) => {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'perfil';
    };

    document.querySelectorAll('[data-disable-on-submit]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                if (button.dataset.loadingLabel && !button.dataset.originalLabel) {
                    button.dataset.originalLabel = button.innerHTML;
                    button.innerHTML = button.dataset.loadingLabel;
                }
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            });
        });
    });

    document.querySelectorAll('[data-toast]').forEach((toast) => {
        window.setTimeout(() => {
            toast.classList.add('is-dismissing');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
            window.setTimeout(() => toast.remove(), 400);
        }, 4500);
    });

    document.querySelectorAll('[data-copy-url]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-copy-url');
            if (!url) {
                return;
            }

            const label = button.textContent;
            const markCopied = () => {
                button.textContent = 'Link copiado';
                window.setTimeout(() => {
                    button.textContent = label;
                }, 2800);
            };

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                    markCopied();
                    return;
                }
            } catch {
                /* fallback below */
            }

            try {
                const ta = document.createElement('textarea');
                ta.value = url;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                markCopied();
            } catch {
                window.prompt('Copie o link:', url);
            }
        });
    });

    document.querySelectorAll('[data-slot-source]').forEach((form) => {
        const slotMap = JSON.parse(form.getAttribute('data-slot-source') || '{}');
        const serviceSelect = form.querySelector('[data-slot-service]');
        const slotTarget = form.querySelector('[data-slot-target]');

        if (!serviceSelect || !slotTarget) {
            return;
        }

        const renderOptions = () => {
            const slots = slotMap[serviceSelect.value] || [];
            slotTarget.innerHTML = '';

            if (!slots.length) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = serviceSelect.value ? 'Sem slots livres' : 'Escolha um serviço';
                slotTarget.appendChild(option);
                return;
            }

            slots.forEach((slot, index) => {
                const option = document.createElement('option');
                option.value = slot;
                option.textContent = slot;
                if (index === 0) {
                    option.selected = true;
                }
                slotTarget.appendChild(option);
            });
        };

        serviceSelect.addEventListener('change', renderOptions);
        renderOptions();
    });

    const colorTarget = document.querySelector('[data-color-target]');
    if (colorTarget) {
        const preview = document.querySelector('[data-color-preview]');
        const syncPreview = () => {
            if (preview) {
                preview.style.background = colorTarget.value;
            }
        };

        document.querySelectorAll('[data-color-value]').forEach((button) => {
            button.addEventListener('click', () => {
                colorTarget.value = button.getAttribute('data-color-value') || colorTarget.value;
                syncPreview();
            });
        });

        colorTarget.addEventListener('input', syncPreview);
        syncPreview();
    }

    document.querySelectorAll('[data-back-button]').forEach((button) => {
        button.addEventListener('click', () => {
            const fallbackUrl = button.getAttribute('data-fallback-url') || '/';
            if (window.history.length > 1) {
                window.history.back();
                window.setTimeout(() => {
                    if (document.visibilityState === 'visible') {
                        window.location.href = fallbackUrl;
                    }
                }, 180);
                return;
            }

            window.location.href = fallbackUrl;
        });
    });

    document.querySelectorAll('[data-menu-toggle]').forEach((toggle) => {
        const panelId = toggle.getAttribute('aria-controls');
        const panel = panelId ? document.getElementById(panelId) : null;
        if (!panel) {
            return;
        }

        const closePanel = () => {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        const openPanel = () => {
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        };

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                closePanel();
            } else {
                openPanel();
            }
        });

        panel.addEventListener('click', (event) => event.stopPropagation());
        document.addEventListener('click', closePanel);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePanel();
            }
        });
    });

    document.querySelectorAll('[data-ai-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const toggleId = trigger.getAttribute('data-ai-target') || 'ai-toggle';
            const toggle = document.getElementById(toggleId);
            if (toggle) {
                toggle.focus();
                toggle.click();
            }
        });
    });

    const slugSource = document.querySelector('[data-slug-source]');
    const slugTarget = document.querySelector('[data-slug-target]');
    if (slugSource && slugTarget) {
        let slugTouched = slugTarget.value.trim() !== '';

        slugTarget.addEventListener('input', () => {
            slugTouched = slugTarget.value.trim() !== '';
        });

        slugSource.addEventListener('input', () => {
            if (!slugTouched || slugTarget.value.trim() === '') {
                slugTarget.value = slugify(slugSource.value);
            }
        });
    }

    document.querySelectorAll('[data-toggle-password]').forEach((toggle) => {
        const field = toggle.closest('.login-field--password');
        if (!field) return;
        const input = field.querySelector('input[type="password"], input[type="text"]');
        const eyeOpen = toggle.querySelector('.eye-open');
        const eyeClosed = toggle.querySelector('.eye-closed');
        const label = field.querySelector('[data-toggle-password-label]');

        const syncState = () => {
            const isPassword = input.type === 'password';
            if (eyeOpen) eyeOpen.style.display = isPassword ? '' : 'none';
            if (eyeClosed) eyeClosed.style.display = isPassword ? 'none' : '';
            if (label) label.textContent = isPassword ? 'mostrar senha' : 'ocultar senha';
            toggle.setAttribute('aria-label', isPassword ? 'mostrar senha' : 'ocultar senha');
        };

        toggle.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            syncState();
        });

        if (label) {
            label.addEventListener('click', () => {
                input.type = input.type === 'password' ? 'text' : 'password';
                syncState();
            });
        }

        syncState();
    });

    document.querySelectorAll('[data-char-source]').forEach((input) => {
        const output = input.parentElement?.querySelector('[data-char-output]');
        const max = Number(input.getAttribute('data-char-max') || input.getAttribute('maxlength') || 0);
        const syncCount = () => {
            if (!output) {
                return;
            }
            const size = input.value.length;
            output.textContent = String(size);
            if (max > 0) {
                output.closest('small')?.classList.toggle('is-danger', size > max);
            }
        };

        input.addEventListener('input', syncCount);
        syncCount();
    });

    // ——— Booking modal open/close ———
    const bookingModal = document.getElementById('booking-modal');
    const bookingModalClose = document.getElementById('booking-modal-close');
    const openBookingBtn = document.getElementById('open-booking-modal');

    function openBookingModal() {
        if (bookingModal) bookingModal.classList.add('is-open');
    }

    function closeBookingModal() {
        if (bookingModal) bookingModal.classList.remove('is-open');
    }

    if (openBookingBtn) {
        openBookingBtn.addEventListener('click', openBookingModal);
    }

    if (bookingModalClose) {
        bookingModalClose.addEventListener('click', closeBookingModal);
    }

    if (bookingModal) {
        bookingModal.addEventListener('click', (e) => {
            if (e.target === bookingModal) closeBookingModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && bookingModal.classList.contains('is-open')) {
                closeBookingModal();
            }
        });
    }

    // "Encaixar" — fill appointment form from waiting list and open modal
    document.querySelectorAll('[data-fill-appointment]').forEach((button) => {
        button.addEventListener('click', () => {
            const nameField = document.getElementById('customer_name');
            const phoneField = document.getElementById('customer_phone');
            if (nameField) {
                nameField.value = button.getAttribute('data-fill-name') || '';
            }
            if (phoneField) {
                phoneField.value = button.getAttribute('data-fill-phone') || '';
            }
            openBookingModal();
            if (nameField) {
                window.setTimeout(() => nameField.focus(), 300);
            }
        });
    });

    // Timeline: click free slot to pre-fill time and open booking modal
    document.querySelectorAll('[data-book-time]').forEach((button) => {
        button.addEventListener('click', () => {
            const time = button.getAttribute('data-book-time');
            if (!time) return;

            const nameField = document.getElementById('customer_name');
            const slotTarget = document.querySelector('[data-slot-target]');
            const serviceSelect = document.querySelector('[data-slot-service]');

            function selectBestSlot() {
                if (!slotTarget) return;
                const options = Array.from(slotTarget.querySelectorAll('option'));
                // Try exact match first
                let found = options.find((opt) => opt.value === time);
                if (!found) {
                    // Find the closest slot that starts at or just before the clicked time
                    const clickedMinutes = timeToMinutes(time);
                    let bestOpt = null;
                    let bestDiff = Infinity;
                    options.forEach((opt) => {
                        if (!opt.value || opt.value === '') return;
                        const diff = Math.abs(timeToMinutes(opt.value) - clickedMinutes);
                        if (diff < bestDiff) {
                            bestDiff = diff;
                            bestOpt = opt;
                        }
                    });
                    found = bestOpt;
                }
                if (found) found.selected = true;
            }

            function timeToMinutes(t) {
                const parts = t.split(':');
                return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
            }

            if (slotTarget) {
                if (serviceSelect && !serviceSelect.value && serviceSelect.options.length > 1) {
                    // Auto-select first service to populate slots
                    serviceSelect.selectedIndex = 1;
                    serviceSelect.dispatchEvent(new Event('change'));
                    // Try after slots populate (async render)
                    window.setTimeout(selectBestSlot, 150);
                } else {
                    selectBestSlot();
                }
            }

            // Open booking modal and focus on name
            openBookingModal();
            if (nameField) {
                window.setTimeout(() => nameField.focus(), 300);
            }
        });
    });

    // Client autocomplete: when selecting from datalist, auto-fill phone
    const clientNameInput = document.getElementById('customer_name');
    const clientPhoneInput = document.getElementById('customer_phone');
    const clientList = document.getElementById('client-list');
    if (clientNameInput && clientPhoneInput && clientList) {
        clientNameInput.addEventListener('input', () => {
            const trimmedValue = clientNameInput.value.trim();
            if (!trimmedValue) return;
            const selectedOption = clientList.querySelector('option[value="' + CSS.escape(trimmedValue) + '"]');
            if (selectedOption && selectedOption.dataset.phone) {
                clientPhoneInput.value = selectedOption.dataset.phone;
            }
        });
    }

    // Native Share API (mobile-friendly sharing)
    document.querySelectorAll('[data-native-share]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-share-url') || window.location.href;
            const title = button.getAttribute('data-share-title') || document.title;
            const text = button.getAttribute('data-share-text') || '';

            if (navigator.share) {
                try {
                    await navigator.share({ title, text, url });
                    return;
                } catch (err) {
                    if (err.name === 'AbortError') return;
                }
            }

            // Fallback: copy to clipboard
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                    const original = button.querySelector('span')?.textContent || button.textContent;
                    const target = button.querySelector('span') || button;
                    target.textContent = 'Link copiado!';
                    setTimeout(() => { target.textContent = original; }, 2800);
                    return;
                }
            } catch { /* fallback */ }

            window.prompt('Copie o link:', url);
        });
    });

    // Settings tabs
    const tabContainer = document.querySelector('[data-settings-tabs]');
    if (tabContainer) {
        const tabs = tabContainer.querySelectorAll('[data-tab]');
        const panels = document.querySelectorAll('[data-tab-panel]');

        const activateTab = (tabName) => {
            tabs.forEach((t) => t.classList.toggle('is-active', t.getAttribute('data-tab') === tabName));
            panels.forEach((p) => p.classList.toggle('is-visible', p.getAttribute('data-tab-panel') === tabName));
            sessionStorage.setItem('settings-active-tab', tabName);
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activateTab(tab.getAttribute('data-tab')));
        });

        const savedTab = sessionStorage.getItem('settings-active-tab');
        const defaultTab = savedTab && tabContainer.querySelector(`[data-tab="${savedTab}"]`) ? savedTab : tabs[0]?.getAttribute('data-tab');
        if (defaultTab) activateTab(defaultTab);
    }

    // Image zoom modal (WhatsApp-style preview)
    const zoomModal = document.createElement('div');
    zoomModal.className = 'image-preview-modal';

    const zoomClose = document.createElement('button');
    zoomClose.className = 'image-preview-modal__close';
    zoomClose.type = 'button';
    zoomClose.setAttribute('aria-label', 'Fechar');
    zoomClose.textContent = '\u00D7';
    zoomModal.appendChild(zoomClose);

    const zoomImg = document.createElement('img');
    zoomImg.className = 'image-preview-modal__img';
    zoomImg.alt = 'Visualização da imagem';
    zoomModal.appendChild(zoomImg);

    document.body.appendChild(zoomModal);

    let zoomScale = 1;

    const openZoom = (src) => {
        // Only allow http(s) and data URIs to prevent XSS via javascript: URIs
        if (src && !/^(https?:|data:image\/)/i.test(src)) return;
        zoomImg.src = src;
        zoomScale = 1;
        zoomImg.style.transform = 'scale(1)';
        zoomModal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    };

    const closeZoom = () => {
        zoomModal.classList.remove('is-open');
        document.body.style.overflow = '';
        zoomImg.src = '';
    };

    zoomClose.addEventListener('click', (e) => {
        e.stopPropagation();
        closeZoom();
    });

    zoomModal.addEventListener('click', (e) => {
        if (e.target === zoomModal) closeZoom();
    });

    zoomModal.addEventListener('wheel', (e) => {
        e.preventDefault();
        zoomScale = Math.max(0.5, Math.min(4, zoomScale + (e.deltaY < 0 ? 0.25 : -0.25)));
        zoomImg.style.transform = `scale(${zoomScale})`;
    }, { passive: false });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && zoomModal.classList.contains('is-open')) closeZoom();
    });

    document.querySelectorAll('[data-image-zoom]').forEach((el) => {
        el.style.cursor = 'pointer';
        el.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const src = el.getAttribute('data-image-zoom');
            if (src) openZoom(src);
        });
    });

    // Image file input preview (update preview when new file selected)
    document.querySelectorAll('input[type="file"][accept="image/*"]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const field = input.closest('.image-upload-field') || input.closest('.field');
                if (!field) return;

                let preview = field.querySelector('.image-upload-preview');
                if (!preview) {
                    const isProfile = input.name === 'profile_image';
                    preview = document.createElement('div');
                    preview.className = `image-upload-preview image-upload-preview--${isProfile ? 'profile' : 'cover'}`;
                    const previewImg = document.createElement('img');
                    previewImg.alt = 'Preview';
                    preview.appendChild(previewImg);
                    const hint = document.createElement('div');
                    hint.className = 'image-upload-preview__zoom-hint';
                    hint.textContent = '\uD83D\uDD0D Ampliar';
                    preview.appendChild(hint);
                    input.before(preview);
                    preview.addEventListener('click', () => {
                        const imgSrc = preview.querySelector('img')?.src;
                        if (imgSrc) openZoom(imgSrc);
                    });
                }

                const img = preview.querySelector('img');
                if (img) {
                    img.src = e.target.result;
                    preview.setAttribute('data-image-zoom', e.target.result);
                }
            };
            reader.readAsDataURL(file);
        });
    });
});
