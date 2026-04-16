<?php $isSpecificSchedule = ($professional['schedule_type'] ?? 'weekly') === 'specific'; ?>
<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker"><?= $isSpecificSchedule ? 'Datas específicas de atendimento' : 'Exceções de horário' ?></span>
                <h1 class="page-title"><?= e($professional['name']) ?></h1>
                <?php if ($isSpecificSchedule): ?>
                    <p class="page-subtitle">Este profissional atende apenas em datas cadastradas. Registre abaixo as datas e horários em que ele estará disponível.</p>
                <?php else: ?>
                    <p class="page-subtitle">Adicione folgas, feriados ou horários especiais. Exceções têm prioridade sobre a disponibilidade semanal.</p>
                <?php endif; ?>
            </div>
            <a class="btn btn-light" href="<?= base_url('vendor/professionals') ?>">← Voltar</a>
        </div>
    </div>

    <?php if ($isSpecificSchedule): ?>
    <div class="card card--section" style="border-left: 4px solid #f59e0b; background: linear-gradient(135deg, #fffbeb, #fef3c7);">
        <div style="display:flex;align-items:flex-start;gap:0.75rem;">
            <span style="font-size:1.5rem;">📌</span>
            <div>
                <strong style="color:#92400e;">Profissional com escala "Datas Específicas"</strong>
                <p class="muted" style="margin-top:0.25rem;">Este profissional <strong>só atenderá nas datas cadastradas aqui</strong>. Clientes e atendentes internos só poderão agendar nas datas registradas. Caso tentem agendar em outra data, receberão um aviso de que o profissional não está disponível.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="app-grid two">
        <!-- Form -->
        <div class="card card--section card--sticky">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker"><?= $isSpecificSchedule ? 'Nova data de atendimento' : 'Nova exceção' ?></span>
                    <h2><?= $isSpecificSchedule ? 'Adicionar data' : 'Adicionar exceção' ?></h2>
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
                        <?php if ($isSpecificSchedule): ?>
                            <label><input type="radio" name="is_available" value="1" checked> Horário de atendimento</label>
                            <label><input type="radio" name="is_available" value="0"> Folga (não atende)</label>
                        <?php else: ?>
                            <label><input type="radio" name="is_available" value="0" checked> Folga (não atende)</label>
                            <label><input type="radio" name="is_available" value="1"> Horário especial</label>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-grid two" id="exception-times" <?= $isSpecificSchedule ? '' : 'style="display:none;"' ?>>
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
                <button class="btn" type="submit" data-loading-label="Salvando..."><?= $isSpecificSchedule ? 'Adicionar data' : 'Adicionar exceção' ?></button>
            </form>
        </div>

        <!-- List -->
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker"><?= $isSpecificSchedule ? 'Datas cadastradas' : 'Exceções cadastradas' ?></span>
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
                                <span class="badge <?= (int) $exc['is_available'] ? 'is-success' : 'is-neutral' ?>"><?= (int) $exc['is_available'] ? ($isSpecificSchedule ? 'Atendimento' : 'Especial') : 'Folga' ?></span>
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
                    <div class="empty-state empty-state--premium">
                        <?= $isSpecificSchedule
                            ? 'Nenhuma data de atendimento cadastrada neste período. Cadastre as datas em que este profissional estará disponível.'
                            : 'Nenhuma exceção cadastrada neste período.' ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
