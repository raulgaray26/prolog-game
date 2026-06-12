<?php

namespace App\Services;

/**
 * PrologService
 *
 * Puente entre Laravel y Prolog.
 * Cada método construye un goal Prolog diseñado para producir
 * salida textual vía writeln/1, luego captura e interpreta esas líneas.
 *
 * Nota: swipl debe estar instalado y disponible en PATH.
 */
class PrologService
{
    /** Ruta absoluta a la base de conocimiento */
    private string $kbPath;

    public function __construct()
    {
        // resources/prolog/ es rastreado por Git y visible para el equipo.
        $this->kbPath = resource_path('prolog/game.pl');
    }

    // =========================================================================
    // Ejecutor central
    // =========================================================================

    /**
     * Lanza swipl con el goal dado y devuelve las líneas impresas por Prolog.
     *
     * El goal DEBE producir su salida mediante write/1 o writeln/1;
     * el valor de retorno de este método son las líneas capturadas de stdout.
     *
     * @param  string        $goal  Goal Prolog válido, SIN punto final.
     * @return array<string>        Líneas de salida (vacías y banners de inicio eliminados).
     */
    private function exec(string $goal): array
    {
        if (!file_exists($this->kbPath)) {
            return ["ERROR: Base de conocimiento no encontrada en {$this->kbPath}"];
        }

        $file = escapeshellarg($this->kbPath);

        // catch/3 evita que una excepción Prolog produzca silencio total.
        // La disyunción al final garantiza que swipl siempre termine (halt).
        $wrapped = "catch(({$goal}), E, (write('ERROR: '), writeln(E))), halt ; halt";
        $g       = escapeshellarg($wrapped);

        $cmd = "swipl -f {$file} -g {$g} 2>&1";
        exec($cmd, $output);

        // Filtra líneas vacías y el banner de bienvenida de SWI-Prolog
        return array_values(array_filter(
            $output,
            fn($l) => trim($l) !== '' && !str_starts_with(trim($l), 'Welcome')
        ));
    }

    // =========================================================================
    // Consultas de listado (sin parámetros)
    // =========================================================================

    /** Devuelve todos los personajes con nivel y vida. */
    public function listCharacters(): array
    {
        $goal = "forall(personaje(N,L,V), "
              . "(write(N), write(' | Nivel: '), write(L), write(' | Vida: '), write(V), nl))";
        return $this->exec($goal);
    }

    /** Devuelve todas las misiones con dificultad y recompensa XP. */
    public function listMissions(): array
    {
        $goal = "forall(mision(ID,Nombre,Dif,XP), "
              . "(write(ID), write(' | '), write(Nombre), "
              . " write(' | Dificultad: '), write(Dif), write(' | XP: '), write(XP), nl))";
        return $this->exec($goal);
    }

    /** Devuelve todos los enemigos con sus puntos de vida. */
    public function listEnemies(): array
    {
        $goal = "forall(enemigo(N,V), (write(N), write(' | Vida: '), write(V), nl))";
        return $this->exec($goal);
    }

    /**
     * Lista pares de personajes que comparten el mismo nivel.
     * P1 @< P2 evita listar (A,B) y (B,A) como pares distintos.
     */
    public function sameLevel(): array
    {
        $goal = "findall(P1-P2, (mismo_nivel(P1,P2), P1 @< P2), Pares), "
              . "(Pares = [] "
              . " -> writeln('No hay personajes con el mismo nivel.') "
              . " ;  forall(member(A-B, Pares), "
              . "    (write(A), write(' y '), write(B), write(' tienen el mismo nivel'), nl)))";
        return $this->exec($goal);
    }

    // =========================================================================
    // Consultas de misiones
    // =========================================================================

    /** Verifica si el personaje cumple el nivel mínimo de la misión. */
    public function canAcceptMission(string $character, string $missionId): array
    {
        $goal = "(puede_aceptar('{$character}', {$missionId})"
              . " -> writeln('SI: {$character} puede aceptar la misión {$missionId}')"
              . " ;  writeln('NO: {$character} no cumple el nivel requerido para {$missionId}'))";
        return $this->exec($goal);
    }

    /** Verifica si el personaje tiene un objeto concreto en su inventario. */
    public function hasRequirement(string $character, string $item): array
    {
        // $item es un átomo Prolog en minúsculas, no necesita comillas simples
        $goal = "(tiene_requerido('{$character}', {$item})"
              . " -> writeln('SI: {$character} tiene {$item} en su inventario')"
              . " ;  writeln('NO: {$character} no tiene {$item} en su inventario'))";
        return $this->exec($goal);
    }

    /** Verifica que el personaje lleve TODOS los objetos requeridos por la misión. */
    public function meetsAllRequirements(string $character, string $missionId): array
    {
        $goal = "(cumple_todos_requerimientos('{$character}', {$missionId})"
              . " -> writeln('SI: {$character} cumple todos los requerimientos de {$missionId}')"
              . " ;  writeln('NO: {$character} no cumple todos los requerimientos de {$missionId}'))";
        return $this->exec($goal);
    }

    /** Lista todas las misiones que el personaje puede aceptar dado su nivel actual. */
    public function availableMissions(string $character): array
    {
        $goal = "misiones_disponibles('{$character}', Misiones), "
              . "(Misiones = [] "
              . " -> writeln('No hay misiones disponibles para {$character}.') "
              . " ;  forall(member(ID-Nombre, Misiones), "
              . "    (write(ID), write(' | '), write(Nombre), nl)))";
        return $this->exec($goal);
    }

    /**
     * Genera el reporte narrativo para uno o varios personajes en una misión.
     *
     * @param string[] $characters
     */
    public function generateReport(array $characters, string $missionId): array
    {
        if (empty($characters)) {
            return ["ERROR: Selecciona al menos un personaje."];
        }

        $list = "['" . implode("','", $characters) . "']";
        $goal = "(generar_reporte({$list}, {$missionId}, Msg)"
              . " -> writeln(Msg)"
              . " ;  writeln('No se pudo generar el reporte. Verifica niveles de los personajes.'))";
        return $this->exec($goal);
    }

    // =========================================================================
    // Consultas de combate
    // =========================================================================

    /**
     * Simula un ataque individual con cada arma del inventario del personaje.
     * Produce una línea por cada arma encontrada.
     */
    public function individualAttack(string $character, string $enemy): array
    {
        // findall recoge todos los resultados posibles antes de imprimirlos
        $goal = "findall(Msg, ataque_individual('{$character}','{$enemy}',Msg), Msgs), "
              . "(Msgs = [] "
              . " -> writeln('Sin resultados: personaje sin armas o enemigo inexistente.') "
              . " ;  forall(member(M, Msgs), writeln(M)))";
        return $this->exec($goal);
    }

    /**
     * Simula el ataque combinado de un grupo de personajes contra un enemigo.
     *
     * @param string[] $characters
     */
    public function groupAttack(array $characters, string $enemy): array
    {
        if (empty($characters)) {
            return ["ERROR: Selecciona al menos un personaje."];
        }

        $list = "['" . implode("','", $characters) . "']";
        $goal = "(ataque_grupal({$list},'{$enemy}',Msg)"
              . " -> writeln(Msg)"
              . " ;  writeln('No se pudo calcular el ataque. Verifica que los personajes tengan armas.'))";
        return $this->exec($goal);
    }

    // =========================================================================
    // Consultas de estadísticas (reglas nuevas)
    // =========================================================================

    /** Calcula la XP acumulada tras N misiones usando la regla recursiva. */
    public function xpAccumulated(int $n): array
    {
        $goal = "xp_acumulada({$n}, Total), "
              . "write('XP acumulada tras '), write({$n}), write(' misiones: '), writeln(Total)";
        return $this->exec($goal);
    }

    /**
     * Suma el daño de TODAS las armas del personaje.
     * Regla nueva: danio_total_personaje/2.
     */
    public function totalDamage(string $character): array
    {
        $goal = "(danio_total_personaje('{$character}', Total)"
              . " -> (write('Daño total de {$character}: '), writeln(Total))"
              . " ;  writeln('El personaje no tiene armas registradas.'))";
        return $this->exec($goal);
    }

    /**
     * Determina si el personaje supera el umbral de élite (nivel >= 6).
     * Regla nueva: es_experto/1.
     */
    public function isExpert(string $character): array
    {
        $goal = "(es_experto('{$character}')"
              . " -> writeln('{$character} ES un personaje experto (nivel >= 6)')"
              . " ;  writeln('{$character} NO es experto todavía (necesita nivel 6)'))";
        return $this->exec($goal);
    }

    /** Fusiona los inventarios de dos personajes en una sola lista. */
    public function mergeTeam(string $p1, string $p2): array
    {
        $goal = "fusionar_equipo('{$p1}','{$p2}',Equipo), "
              . "write('Inventario fusionado ({$p1} + {$p2}): '), writeln(Equipo)";
        return $this->exec($goal);
    }
}