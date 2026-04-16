<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Exceções de horário</span>
                <h1 class="page-title"><?= e($professional['name']) ?></h1>
                <p class="page-subtitle">Adicione folgas, feriados ou horários especiais. Exceções têm prioridade sobre a disponibilidade semanal.</p>
            </div>
            <a class="btn btn-light" href="<?= base_url('vendor/professionals') ?>">← Voltar</a>
        </div>
    </div>

    <div class="app-grid two">
        <!-- Form -->
        <div class="card card--section card--sticky">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Nova exceção</span>
                    <h2>Adicionar exceção</h2>
                </div>
            </div>

            <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/professionals/' . $professional['id'] . '/exceptions') ?>" data-disable-on-submit>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="exc_date">Datas <small class="muted">(selecione uma ou mais)</small></label>
                    <div id="batch-dates-container">
                        <div class="batch-date-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input name="exception_dates[]" type="date" required style="flex: 1;">
                            <button type="button" class="btn btn-light btn-remove-date" style="display:none;" title="Remover">✕</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-light" id="add-date-btn" style="margin-top: 0.25rem;">+ Adicionar outra data</button>
                </div>
                <script>
                (function(){
                    var container = document.getElementById('batch-dates-container');
                    var addBtn = document.getElementById('add-date-btn');
                    function updateRemoveButtons(){
                        var rows = container.querySelectorAll('.batch-date-row');
                        rows.forEach(function(row){
                            row.querySelector('.btn-remove-date').style.display = rows.length > 1 ? '' : 'none';
                        });
                    }
                    addBtn.addEventListener('click', function(){
                        var row = document.createElement('div');
                        row.className = 'batch-date-row';
                        row.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';
                        row.innerHTML = '<input name="exception_dates[]" type="date" required style="flex: 1;">'
                            + '<button type="button" class="btn btn-light btn-remove-date" title="Remover">✕</button>';
                        container.appendChild(row);
                        updateRemoveButtons();
                    });
                    container.addEventListener('click', function(e){
                        if(e.target.classList.contains('btn-remove-date')){
                            e.target.closest('.batch-date-row').remove();
                            updateRemoveButtons();
                        }
                    });
                })();
                </script>
                <div class="field">
                    <label>Tipo</label>
                    <div style="display: flex; gap: 1rem;">
                        <label><input type="radio" name="is_available" value="0" checked> Folga (não atende)</label>
                        <label><input type="radio" name="is_available" value="1"> Horário especial</label>
                    </div>
                </div>
                <div class="form-grid two" id="exception-times">
                    <div class="field">
                        <label for="exc_start">Início</label>
                        <input id="exc_start" name="start_time" type="time" value="08:00">
                    </div>
                    <div class="field">
                        <label for="exc_end">Fim</label>
                        <input id="exc_end" name="end_time" type="time" value="18:00">
                    </div>
                </div>
                <script>
                document.querySelectorAll('input[name="is_available"]').forEach(function(r){
                    r.addEventListener('change',function(){
                        document.getElementById('exception-times').style.display=this.value==='1'?'':'none';
                    });
                });
                // Initialize: hide times if "Folga" is selected by default
                (function(){
                    var checked=document.querySelector('input[name="is_available"]:checked');
                    if(checked&&checked.value==='0'){document.getElementById('exception-times').style.display='none';}
                })();
                </script>
                <div class="field">
                    <label for="exc_reason">Motivo (opcional)</label>
                    <input id="exc_reason" name="reason" type="text" placeholder="Ex.: Feriado, consulta médica...">
                </div>
                <button class="btn" type="submit" data-loading-label="Salvando...">Adicionar exceção</button>
            </form>
        </div>

        <!-- List -->
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Exceções cadastradas</span>
                    <h2>Período: <?= format_date($start_date) ?> a <?= format_date($end_date) ?></h2>
                </div>
            </div>

            <div class="service-list service-list--premium">
                <?php foreach ($exceptions as $exc): ?>
                    <article class="service-item service-item--premium">
                        <div class="stack stack--compact" style="width: 100%;">
                            <div class="service-head">
                                <div>
                                    <strong><?= format_date($exc['exception_date']) ?></strong>
                                    <?php if ((int) $exc['is_available']): ?>
                                        <br><span class="muted">Horário especial: <?= e(substr($exc['start_time'], 0, 5)) ?> - <?= e(substr($exc['end_time'], 0, 5)) ?></span>
                                    <?php else: ?>
                                        <br><span class="muted">Folga — não atende</span>
                                    <?php endif; ?>
                                    <?php if (!empty($exc['reason'])): ?>
                                        <br><span class="muted">Motivo: <?= e($exc['reason']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= (int) $exc['is_available'] ? 'is-success' : 'is-neutral' ?>"><?= (int) $exc['is_available'] ? 'Especial' : 'Folga' ?></span>
                            </div>
                            <div class="inline-actions">
                                <form method="post" action="<?= base_url('vendor/professionals/' . $professional['id'] . '/exceptions/' . $exc['exception_date'] . '/delete') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger" type="submit">Remover</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (empty($exceptions)): ?>
                    <div class="empty-state empty-state--premium">Nenhuma exceção cadastrada neste período.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
