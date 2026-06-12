<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prolog Game Console</title>
    {{-- Bootstrap 5 vía CDN: sin pipeline de assets para mantener el proyecto simple --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Tema oscuro para coincidir con la ambientación del juego y mejorar legibilidad */
        :root {
            --bg-main:    #0d1117;
            --bg-card:    #161b22;
            --bg-input:   #21262d;
            --border:     #30363d;
            --accent:     #e94560;
            --text:       #c9d1d9;
            --muted:      #8b949e;
        }
        body           { background: var(--bg-main); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; }
        .card          { background: var(--bg-card); border-color: var(--border); }
        .card-header   { background: var(--bg-input); border-bottom-color: var(--border); }
        .form-select,
        .form-control  { background: var(--bg-input) !important; color: var(--text) !important; border-color: var(--border) !important; }
        .form-select:focus,
        .form-control:focus { box-shadow: 0 0 0 .2rem rgba(233,69,96,.25); }
        .form-check-input          { background-color: var(--bg-input); border-color: var(--border); }
        .form-check-input:checked  { background-color: var(--accent); border-color: var(--accent); }
        .btn-run       { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn-run:hover { background: #c73652; border-color: #c73652; color: #fff; }
        .result-line   { padding: 6px 12px; border-bottom: 1px solid var(--border);
                         font-family: 'Courier New', monospace; font-size: .88rem; }
        .result-line:last-child          { border-bottom: none; }
        .result-line:nth-child(odd)      { background: rgba(255,255,255,.02); }
        .form-label    { color: var(--muted); font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
        .text-muted    { color: var(--muted) !important; }
        .badge-q       { background: var(--accent); }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

    <div class="text-center mb-4">
        <h1 class="display-6 fw-bold">Prolog Game Console</h1>
        <p class="text-muted mb-0">Consultas en tiempo real sobre la base de conocimiento del juego</p>
    </div>

    <div class="row g-4">

        {{-- ── Panel izquierdo: formulario de consulta ── --}}
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-semibold">Nueva Consulta</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('game.query') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Tipo de consulta</label>
                            <select name="query_type" id="queryType" class="form-select form-select-sm" onchange="syncParams()">
                                <option value="">— Selecciona —</option>
                                <optgroup label="Listados">
                                    <option value="list_characters" @selected(($type??'')==='list_characters')>Listar personajes</option>
                                    <option value="list_missions"   @selected(($type??'')==='list_missions')>Listar misiones</option>
                                    <option value="list_enemies"    @selected(($type??'')==='list_enemies')>Listar enemigos</option>
                                    <option value="same_level"      @selected(($type??'')==='same_level')>Mismo nivel</option>
                                </optgroup>
                                <optgroup label="Misiones">
                                    <option value="can_accept"              @selected(($type??'')==='can_accept')>¿Puede aceptar misión?</option>
                                    <option value="has_requirement"         @selected(($type??'')==='has_requirement')>¿Tiene objeto requerido?</option>
                                    <option value="meets_all_requirements"  @selected(($type??'')==='meets_all_requirements')>¿Cumple todos los req.?</option>
                                    <option value="available_missions"      @selected(($type??'')==='available_missions')>Misiones disponibles</option>
                                    <option value="generate_report"         @selected(($type??'')==='generate_report')>Reporte narrativo</option>
                                </optgroup>
                                <optgroup label="Combate">
                                    <option value="individual_attack" @selected(($type??'')==='individual_attack')>Ataque individual</option>
                                    <option value="group_attack"      @selected(($type??'')==='group_attack')>Ataque grupal</option>
                                </optgroup>
                                <optgroup label="Estadísticas">
                                    <option value="xp_accumulated" @selected(($type??'')==='xp_accumulated')>XP acumulada</option>
                                    <option value="total_damage"    @selected(($type??'')==='total_damage')>Daño total del personaje</option>
                                    <option value="is_expert"       @selected(($type??'')==='is_expert')>¿Es experto?</option>
                                    <option value="merge_team"      @selected(($type??'')==='merge_team')>Fusionar equipos</option>
                                </optgroup>
                            </select>
                        </div>

                        {{-- Personaje único --}}
                        <div id="p-character" class="param-block d-none mb-3">
                            <label class="form-label">Personaje</label>
                            <select name="character" class="form-select form-select-sm">
                                @foreach($characters as $c)
                                    <option value="{{ $c }}" @selected(request('character')===$c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Personaje 1 (para fusionar equipo) --}}
                        <div id="p-character1" class="param-block d-none mb-3">
                            <label class="form-label">Personaje 1</label>
                            <select name="character1" class="form-select form-select-sm">
                                @foreach($characters as $c)
                                    <option value="{{ $c }}" @selected(request('character1')===$c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Personaje 2 (para fusionar equipo) --}}
                        <div id="p-character2" class="param-block d-none mb-3">
                            <label class="form-label">Personaje 2</label>
                            <select name="character2" class="form-select form-select-sm">
                                @foreach($characters as $c)
                                    <option value="{{ $c }}" @selected(request('character2')===$c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Múltiples personajes (checkboxes) --}}
                        <div id="p-characters" class="param-block d-none mb-3">
                            <label class="form-label">Personajes (elige 1 o más)</label>
                            <div class="ps-1">
                                @foreach($characters as $c)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="characters[]" value="{{ $c }}"
                                               id="chk_{{ $loop->index }}"
                                               @checked(in_array($c, request('characters', [])))>
                                        <label class="form-check-label small" for="chk_{{ $loop->index }}">{{ $c }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Misión --}}
                        <div id="p-mission" class="param-block d-none mb-3">
                            <label class="form-label">Misión</label>
                            <select name="mission" class="form-select form-select-sm">
                                @foreach($missions as $m)
                                    <option value="{{ $m }}" @selected(request('mission')===$m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Enemigo --}}
                        <div id="p-enemy" class="param-block d-none mb-3">
                            <label class="form-label">Enemigo</label>
                            <select name="enemy" class="form-select form-select-sm">
                                @foreach($enemies as $e)
                                    <option value="{{ $e }}" @selected(request('enemy')===$e)>{{ $e }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Objeto/ítem --}}
                        <div id="p-item" class="param-block d-none mb-3">
                            <label class="form-label">Objeto</label>
                            <select name="item" class="form-select form-select-sm">
                                @foreach($items as $i)
                                    <option value="{{ $i }}" @selected(request('item')===$i)>{{ $i }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Número de misiones (para XP) --}}
                        <div id="p-count" class="param-block d-none mb-3">
                            <label class="form-label">Número de misiones</label>
                            <input type="number" name="missions_count" class="form-control form-control-sm"
                                   min="0" max="30" value="{{ request('missions_count', 5) }}">
                        </div>

                        <button type="submit" class="btn btn-run btn-sm w-100 mt-2">
                            ▶ Ejecutar consulta
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Panel derecho: resultados ── --}}
        <div class="col-md-8 col-lg-9">
            <div class="card" style="min-height: 400px;">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">Resultado</h6>
                    @isset($type)
                        <span class="badge badge-q small">{{ $type }}</span>
                    @endisset
                </div>
                <div class="card-body p-0">
                    @if(isset($error))
                        <div class="alert alert-danger m-3">{{ $error }}</div>

                    @elseif(isset($results))
                        @if(empty($results))
                            <p class="text-muted p-3 mb-0">
                                Sin resultados. Asegúrate de que <code>swipl</code> esté en PATH
                                y los parámetros sean correctos.
                            </p>
                        @else
                            <div>
                                @foreach($results as $line)
                                    <div class="result-line">{{ $line }}</div>
                                @endforeach
                            </div>
                        @endif

                    @else
                        <div class="p-4 text-center text-muted mt-3">
                            <p>Selecciona una consulta y presiona <strong>▶ Ejecutar</strong>.</p>
                            <small>
                                Base de conocimiento:
                                {{ count($characters) }} personajes ·
                                {{ count($missions) }} misiones ·
                                {{ count($enemies) }} enemigos
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    /**
     * Mapea cada tipo de consulta a los IDs de los bloques de parámetros que necesita.
     * Los tipos no listados aquí no requieren parámetros adicionales.
     */
    const PARAM_MAP = {
        'can_accept':             ['p-character', 'p-mission'],
        'has_requirement':        ['p-character', 'p-item'],
        'meets_all_requirements': ['p-character', 'p-mission'],
        'available_missions':     ['p-character'],
        'generate_report':        ['p-characters', 'p-mission'],
        'individual_attack':      ['p-character', 'p-enemy'],
        'group_attack':           ['p-characters', 'p-enemy'],
        'xp_accumulated':         ['p-count'],
        'total_damage':           ['p-character'],
        'is_expert':              ['p-character'],
        'merge_team':             ['p-character1', 'p-character2'],
    };

    function syncParams() {
        // Oculta todos los bloques, luego muestra solo los necesarios
        document.querySelectorAll('.param-block').forEach(el => el.classList.add('d-none'));
        const needed = PARAM_MAP[document.getElementById('queryType').value] || [];
        needed.forEach(id => document.getElementById(id)?.classList.remove('d-none'));
    }

    // Restaura el tipo seleccionado tras el POST (evita que el dropdown quede en blanco)
    document.addEventListener('DOMContentLoaded', syncParams);
</script>
</body>
</html>