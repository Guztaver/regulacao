@extends('layouts.print')

@section('title', 'Entrada - ' . $entry->title)

@section('content')
    <div class="header">
        <img src="/images/logo-prefeitura.png?v=2" alt="Lista da regulação Logo" class="header-logo">
        <h1>FICHA DE ENTRADA</h1>
        <p>Lista da regulação - Documento de Impressão</p>
        <p style="margin-top: 10px; font-size: 12px; color: #666;">Documento gerado por: {{ $currentUser->name ?? 'N/A' }}</p>
    </div>

    <!-- Entry Basic Information -->
    <div class="section">
        <div class="section-title">Informações da Entrada</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">ID da Entrada</div>
                <div class="info-value">{{ $entry->id }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status Atual</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $entry->currentStatus->slug ?? 'unknown' }}">
                        {{ $entry->currentStatus->name ?? 'Desconhecido' }}
                    </span>
                </div>
            </div>
            <div class="info-item full-width">
                <div class="info-label">Título da Entrada</div>
                <div class="info-value">{{ $entry->title }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Data de Criação</div>
                <div class="info-value">{{ $entry->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Última Atualização</div>
                <div class="info-value">{{ $entry->updated_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Criado por</div>
                <div class="info-value">{{ $entry->createdBy->name ?? 'N/A' }}</div>
            </div>
            @if($entry->scheduled_exam_date)
            <div class="info-item">
                <div class="info-label">Data do Exame Agendado</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($entry->scheduled_exam_date)->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Patient Information -->
    <div class="section">
        <div class="section-title">Informações do Paciente</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nome Completo</div>
                <div class="info-value">{{ $entry->patient->name ?? 'N/A' }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Telefone</div>
                <div class="info-value">{{ $entry->patient->phone ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Número do SUS</div>
                <div class="info-value">{{ $entry->patient->sus_number ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ID do Paciente</div>
                <div class="info-value">{{ $entry->patient->id ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Cadastro do Paciente</div>
                <div class="info-value">{{ $entry->patient->created_at ? $entry->patient->created_at->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Status History -->
    @if($entry->statusTransitions && $entry->statusTransitions->count() > 0)
    <div class="section">
        <div class="section-title">Histórico de Status</div>
        <div class="timeline">
            @foreach($entry->statusTransitions as $transition)
            <div class="timeline-item">
                <div class="timeline-date">
                    {{ $transition->created_at->format('d/m/Y') }}<br>
                    <small>{{ $transition->created_at->format('H:i') }}</small>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">
                        @if($transition->fromStatus)
                            {{ $transition->fromStatus->name }} → {{ $transition->toStatus->name }}
                        @else
                            Entrada criada com status: {{ $transition->toStatus->name }}
                        @endif
                    </div>
                    <div class="timeline-description">
                        @if($transition->reason)
                            <strong>Motivo:</strong> {{ $transition->reason }}<br>
                        @endif
                        @if($transition->user)
                            <strong>Por:</strong> {{ $transition->user->name }}<br>
                        @endif
                        @if($transition->scheduled_date)
                            <strong>Data agendada:</strong> {{ \Carbon\Carbon::parse($transition->scheduled_date)->format('d/m/Y H:i') }}<br>
                        @endif
                        @if($transition->metadata && count($transition->metadata) > 0)
                            <strong>Dados adicionais:</strong>
                            @foreach($transition->metadata as $key => $value)
                                @switch($key)
                                    @case('title')
                                        Título: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('scheduled_date')
                                        Data agendada: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('changes')
                                        Alterações: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('migrated_from_boolean_fields')
                                        Migrado de campos booleanos: {{ is_array($value) ? json_encode($value) : ($value ? 'Sim' : 'Não') }}@if(!$loop->last), @endif
                                        @break
                                    @case('notes')
                                        Observações: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('priority')
                                        Prioridade: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('location')
                                        Local: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('doctor')
                                        Médico: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('reason')
                                        Motivo: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @default
                                        {{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                @endswitch
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Documents -->
    @if($entry->documents && $entry->documents->count() > 0)
    <div class="section">
        <div class="section-title">Documentos Anexados ({{ $entry->documents->count() }})</div>
        <div class="documents-list">
            @foreach($entry->documents as $document)
            <div class="document-item">
                <div class="document-info">
                    <div class="document-name">{{ $document->document_type_label }}</div>
                    <div class="document-meta">
                        <strong>Adicionado por:</strong> {{ $document->uploadedBy->name ?? 'N/A' }}<br>
                        @if($document->description)
                            <strong>Descrição:</strong> {{ $document->description }}<br>
                        @endif
                        <strong>Enviado em:</strong> {{ $document->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Timeline (if different from status transitions) -->
    @if($entry->timeline && $entry->timeline->count() > 0)
    <div class="section">
        <div class="section-title">Linha do Tempo de Ações</div>
        <div class="timeline">
            @foreach($entry->timeline as $timelineItem)
            <div class="timeline-item">
                <div class="timeline-date">
                    {{ $timelineItem->performed_at ? \Carbon\Carbon::parse($timelineItem->performed_at)->format('d/m/Y') : $timelineItem->created_at->format('d/m/Y') }}<br>
                    <small>{{ $timelineItem->performed_at ? \Carbon\Carbon::parse($timelineItem->performed_at)->format('H:i') : $timelineItem->created_at->format('H:i') }}</small>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">
                        @switch($timelineItem->action)
                            @case('created')
                                Criado
                                @break
                            @case('completed')
                                Concluído
                                @break
                            @case('exam_scheduled')
                                Exame Agendado
                                @break
                            @case('exam_ready')
                                Exame Pronto
                                @break
                            @case('updated')
                                Atualizado
                                @break
                            @case('deleted')
                                Excluído
                                @break
                            @default
                                {{ ucfirst($timelineItem->action) }}
                        @endswitch
                    </div>
                    <div class="timeline-description">
                        @if($timelineItem->description)
                            {{ $timelineItem->description }}<br>
                        @endif
                        @if($timelineItem->user)
                            <strong>Por:</strong> {{ $timelineItem->user->name }}<br>
                        @endif
                        @if($timelineItem->metadata && count($timelineItem->metadata) > 0)
                            <strong>Dados:</strong>
                            @foreach($timelineItem->metadata as $key => $value)
                                @switch($key)
                                    @case('title')
                                        Título: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('scheduled_date')
                                        Data agendada: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('changes')
                                        Alterações: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('migrated_from_boolean_fields')
                                        Migrado de campos booleanos: {{ is_array($value) ? json_encode($value) : ($value ? 'Sim' : 'Não') }}@if(!$loop->last), @endif
                                        @break
                                    @case('notes')
                                        Observações: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('priority')
                                        Prioridade: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('location')
                                        Local: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('doctor')
                                        Médico: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @case('reason')
                                        Motivo: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                        @break
                                    @default
                                        {{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}@if(!$loop->last), @endif
                                @endswitch
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Signature Area -->
    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Assinatura do Responsável</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Data: ___/___/______</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Documento gerado automaticamente em {{ now()->format('d/m/Y H:i') }}</p>
        <p>Adicionado por: {{ $currentUser->name ?? 'N/A' }}</p>
        <p>Lista da regulação - Ficha de Entrada #{{ $entry->id }}</p>
        <p>Made with ❤️ by Gustavo M. (https://github.com/guztaver)</p>
    </div>
@endsection
