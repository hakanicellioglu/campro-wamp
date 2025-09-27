// Nexa ürün & varyant yönetimi betiği
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const productForm = document.getElementById('product-form');
    const variantForm = document.getElementById('variant-form');
    const addLayerBtn = document.getElementById('add-layer');
    const layerTableBody = document.querySelector('#layer-table tbody');
    const variantListBody = document.querySelector('#variant-list tbody');
    const variantSearch = document.getElementById('variant-search');
    const clientAlerts = document.getElementById('client-alerts');
    const templateButtons = document.querySelectorAll('button[data-template]');
    const productIdInput = document.getElementById('product-id');

    if (!productForm || !variantForm || !layerTableBody || !clientAlerts) {
        return;
    }

    let dicts = {
        categories: [],
        systems: [],
        layer_types: [],
        materials: []
    };
    let currentProductId = null;

    const showAlert = (message, type = 'success') => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>`;
        clientAlerts.prepend(wrapper.firstElementChild);
        setTimeout(() => {
            const alertEl = wrapper.querySelector('.alert');
            if (alertEl) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                bsAlert.close();
            }
        }, 6000);
    };

    const sendRequest = async (url, options = {}) => {
        const opts = Object.assign({
            headers: {
                'Content-Type': 'application/json; charset=UTF-8',
                'X-CSRF-Token': csrfToken
            },
            credentials: 'same-origin'
        }, options);
        const response = await fetch(url, opts);
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.ok === false) {
            const errorMessage = data.error || 'İşlem sırasında hata oluştu.';
            throw new Error(errorMessage);
        }
        return data;
    };

    const populateSelect = (selectEl, items, labelKey = 'name') => {
        selectEl.innerHTML = '<option value="">Seçiniz...</option>';
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item[labelKey];
            selectEl.appendChild(option);
        });
    };

    const reindexLayers = () => {
        [...layerTableBody.querySelectorAll('tr')].forEach((row, index) => {
            row.dataset.sequence = index + 1;
            const sequenceCell = row.querySelector('.layer-sequence');
            if (sequenceCell) {
                sequenceCell.textContent = index + 1;
                sequenceCell.setAttribute('data-label', 'Sıra');
            }
        });
    };

    const handleLayerTypeChange = (row, selectedCode) => {
        const materialWrap = row.querySelector('.layer-material');
        const airGapWrap = row.querySelector('.layer-air-gap');
        const noteInput = row.querySelector('.layer-note');
        materialWrap.innerHTML = '';
        airGapWrap.innerHTML = '';

        if (selectedCode === 'air_gap') {
            const airInput = document.createElement('input');
            airInput.type = 'number';
            airInput.className = 'form-control form-control-sm';
            airInput.min = '0';
            airInput.step = '0.1';
            airInput.placeholder = 'mm';
            airInput.required = true;
            airInput.name = 'air_gap_mm';
            airGapWrap.appendChild(airInput);
            airGapWrap.setAttribute('data-label', 'Hava Boşluğu (mm)');
            materialWrap.setAttribute('data-label', 'Malzeme');
        } else {
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            select.required = true;
            select.name = 'material_id';

            const baseKind = selectedCode === 'film' ? 'film' : 'glass';
            const filteredMaterials = dicts.materials.filter(mat => mat.base_kind === baseKind);
            select.innerHTML = '<option value="">Malzeme Seçin...</option>';
            filteredMaterials.forEach(mat => {
                const option = document.createElement('option');
                option.value = mat.id;
                const temperedLabel = mat.is_tempered ? ' Temperli' : '';
                const edgeLabel = mat.edge_finish ? ` ${mat.edge_finish}` : '';
                option.textContent = `${mat.thickness_mm} mm ${mat.name}${temperedLabel}${edgeLabel}`;
                select.appendChild(option);
            });
            materialWrap.appendChild(select);
            materialWrap.setAttribute('data-label', 'Malzeme');
            airGapWrap.setAttribute('data-label', 'Hava Boşluğu (mm)');
        }

        if (noteInput) {
            noteInput.placeholder = selectedCode === 'air_gap' ? 'Örn. Argon dolgu' : 'Örn. dış yüzey';
        }
    };

    const createLayerRow = (data = {}) => {
        const row = document.createElement('tr');
        row.classList.add('layer-row');
        row.draggable = true;

        row.innerHTML = `
            <td class="layer-sequence fw-semibold text-muted"></td>
            <td data-label="Katman Tipi">
                <select class="form-select form-select-sm layer-type" required>
                    ${dicts.layer_types.map(lt => `<option value="${lt.code}">${lt.label}</option>`).join('')}
                </select>
            </td>
            <td class="layer-material" data-label="Malzeme"></td>
            <td class="layer-air-gap" data-label="Hava Boşluğu (mm)"></td>
            <td data-label="Açıklama">
                <input type="text" class="form-control form-control-sm layer-note" name="notes" maxlength="255" value="${data.notes || ''}">
            </td>
            <td class="text-end" data-label="İşlemler">
                <button type="button" class="btn btn-outline-danger btn-sm remove-layer">Sil</button>
            </td>
        `;

        const layerTypeSelect = row.querySelector('.layer-type');
        if (data.layer_type_code) {
            layerTypeSelect.value = data.layer_type_code;
        }
        handleLayerTypeChange(row, layerTypeSelect.value);

        if (data.layer_type_code === 'air_gap' && data.air_gap_mm) {
            row.querySelector('input[name="air_gap_mm"]').value = data.air_gap_mm;
        }
        if (data.material_id) {
            const select = row.querySelector('select[name="material_id"]');
            if (select) {
                select.value = String(data.material_id);
            }
        }

        layerTypeSelect.addEventListener('change', () => handleLayerTypeChange(row, layerTypeSelect.value));
        row.addEventListener('dragstart', event => {
            row.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('dragging');
            reindexLayers();
        });

        row.querySelector('.remove-layer').addEventListener('click', () => {
            row.remove();
            if (layerTableBody.children.length === 0) {
                showAlert('Katman listesi boş; en az bir katman ekleyin.', 'warning');
            }
            reindexLayers();
        });

        return row;
    };

    const initDragAndDrop = () => {
        layerTableBody.addEventListener('dragover', event => {
            event.preventDefault();
            const dragging = layerTableBody.querySelector('.dragging');
            if (!dragging) {
                return;
            }
            const afterElement = [...layerTableBody.querySelectorAll('tr:not(.dragging)')].find(row => {
                const box = row.getBoundingClientRect();
                return event.clientY < box.top + box.height / 2;
            });
            if (afterElement == null) {
                layerTableBody.appendChild(dragging);
            } else {
                layerTableBody.insertBefore(dragging, afterElement);
            }
        });
    };

    const collectLayerData = () => {
        return [...layerTableBody.querySelectorAll('tr')].map((row, index) => {
            const layerType = row.querySelector('.layer-type').value;
            const note = row.querySelector('.layer-note').value.trim();
            const layer = {
                sequence_no: index + 1,
                layer_type_code: layerType,
                notes: note || undefined
            };
           if (layerType === 'air_gap') {
               const airInput = row.querySelector('input[name="air_gap_mm"]');
                layer.air_gap_mm = parseFloat(airInput.value);
                if (!Number.isFinite(layer.air_gap_mm)) {
                    throw new Error('Hava boşluğu değeri sayısal olmalıdır.');
                }
            } else {
                const materialSelect = row.querySelector('select[name="material_id"]');
                layer.material_id = parseInt(materialSelect.value, 10);
                if (!Number.isInteger(layer.material_id)) {
                    throw new Error('Malzeme seçimi eksik.');
                }
            }
            return layer;
        });
    };

    const refreshVariantList = async () => {
        if (!currentProductId) {
            return;
        }
        try {
            const data = await sendRequest(`/api/variants/list.php?product_id=${currentProductId}`);
            variantListBody.innerHTML = '';
            data.variants.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.sku}</td>
                    <td>${item.variant_name}</td>
                    <td>${item.recipe || '-'}</td>
                    <td>${Number(item.base_price).toFixed(2)} ${item.currency}</td>
                `;
                row.dataset.search = `${item.sku} ${item.variant_name} ${item.recipe || ''}`.toLowerCase();
                variantListBody.appendChild(row);
            });
            if (!data.variants.length) {
                variantListBody.innerHTML = '<tr class="placeholder-row"><td colspan="4" class="text-center text-muted">Kayıtlı varyant bulunamadı.</td></tr>';
            }
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    };

    const applyTemplate = templateCode => {
        if (!dicts.materials.length) {
            return;
        }
        layerTableBody.innerHTML = '';
        const templates = {
            '4-12-4': [
                { layer_type_code: 'glass', material_code: 'GLS-CLR-4T' },
                { layer_type_code: 'air_gap', air_gap_mm: 12 },
                { layer_type_code: 'glass', material_code: 'GLS-CLR-4' }
            ],
            '6-12-6': [
                { layer_type_code: 'glass', material_code: 'GLS-CLR-6T' },
                { layer_type_code: 'air_gap', air_gap_mm: 12 },
                { layer_type_code: 'glass', material_code: 'GLS-CLR-6' }
            ]
        };
        const templateLayers = templates[templateCode];
        if (!templateLayers) {
            return;
        }
        templateLayers.forEach(layer => {
            const material = layer.material_code ? dicts.materials.find(mat => mat.material_code === layer.material_code) : null;
            const row = createLayerRow({
                layer_type_code: layer.layer_type_code,
                material_id: material ? material.id : undefined,
                air_gap_mm: layer.air_gap_mm
            });
            layerTableBody.appendChild(row);
            handleLayerTypeChange(row, row.querySelector('.layer-type').value);
            if (material) {
                const select = row.querySelector('select[name="material_id"]');
                if (select) {
                    select.value = material.id;
                }
            }
            if (layer.layer_type_code === 'air_gap' && layer.air_gap_mm) {
                row.querySelector('input[name="air_gap_mm"]').value = layer.air_gap_mm;
            }
        });
        reindexLayers();
    };

    const filterVariants = query => {
        const q = query.trim().toLowerCase();
        [...variantListBody.querySelectorAll('tr')].forEach(row => {
            if (row.classList.contains('placeholder-row')) {
                row.style.display = q ? 'none' : '';
                return;
            }
            const searchData = row.dataset.search || '';
            row.style.display = searchData.includes(q) ? '' : 'none';
        });
    };

    const resetVariantForm = () => {
        variantForm.reset();
        layerTableBody.innerHTML = '';
        reindexLayers();
    };

    const init = async () => {
        try {
            const data = await sendRequest('/api/dicts/list.php', { method: 'GET', headers: { 'Accept': 'application/json' } });
            dicts = data;
            populateSelect(document.getElementById('category-id'), dicts.categories);
            populateSelect(document.getElementById('system-type-id'), dicts.systems);
        } catch (error) {
            showAlert(error.message, 'danger');
        }
        initDragAndDrop();
    };

    productForm?.addEventListener('submit', async event => {
        event.preventDefault();
        const formData = new FormData(productForm);
        const payload = {
            category_id: Number(formData.get('category_id')),
            system_type_id: Number(formData.get('system_type_id')),
            name: formData.get('name').trim(),
            description: formData.get('description').trim()
        };
        try {
            const data = await sendRequest('/api/products/add.php', { method: 'POST', body: JSON.stringify(payload) });
            currentProductId = data.product_id;
            productIdInput.value = currentProductId;
            showAlert('Ürün bilgileri kaydedildi. Şimdi varyant ekleyebilirsiniz.');
            refreshVariantList();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });

    document.getElementById('reset-product')?.addEventListener('click', () => {
        productForm.reset();
        currentProductId = null;
        productIdInput.value = '';
        variantListBody.innerHTML = '<tr class="placeholder-row"><td colspan="4" class="text-center text-muted">Önce bir ürün seçin veya oluşturun.</td></tr>';
        resetVariantForm();
    });

    addLayerBtn?.addEventListener('click', () => {
        if (!dicts.layer_types.length) {
            showAlert('Katman tipleri yüklenemedi.', 'warning');
            return;
        }
        const row = createLayerRow({ layer_type_code: dicts.layer_types[0].code });
        layerTableBody.appendChild(row);
        handleLayerTypeChange(row, row.querySelector('.layer-type').value);
        reindexLayers();
    });

    templateButtons.forEach(button => {
        button.addEventListener('click', () => applyTemplate(button.dataset.template));
    });

    variantForm?.addEventListener('submit', async event => {
        event.preventDefault();
        if (!currentProductId) {
            showAlert('Önce ürün bilgilerini kaydedin.', 'warning');
            return;
        }
        if (layerTableBody.children.length === 0) {
            showAlert('Varyant için en az bir katman ekleyin.', 'warning');
            return;
        }
        try {
            const layers = collectLayerData();
            const payload = {
                product_id: currentProductId,
                sku: document.getElementById('variant-sku').value.trim(),
                variant_name: document.getElementById('variant-name').value.trim(),
                base_price: Number(document.getElementById('variant-price').value || 0),
                currency: document.getElementById('variant-currency').value.trim().toUpperCase() || 'TRY',
                layers
            };
            const data = await sendRequest('/api/variants/add.php', { method: 'POST', body: JSON.stringify(payload) });
            showAlert('Varyant başarıyla oluşturuldu.');
            resetVariantForm();
            refreshVariantList();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });

    variantSearch?.addEventListener('input', event => {
        filterVariants(event.target.value);
    });

    init();
})();
