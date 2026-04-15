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

    // "Encaixar" — fill appointment form from waiting list
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
            if (nameField) {
                nameField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                nameField.focus();
            }
        });
    });

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
});
