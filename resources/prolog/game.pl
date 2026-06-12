% ============================================================
% game.pl — Base de conocimiento del juego Prolog
% Extiende Taller5.pl con nuevas reglas para la interfaz Laravel.
%
% UTF-8 habilitado explícitamente para nombres con tildes.
% ============================================================
:- encoding(utf8).
:- use_module(library(lists)).   % sum_list/2, etc.

% ── HECHOS: Personajes (Nombre, Nivel, Vida) ─────────────────
personaje('Elara', 5, 100).
personaje('Kael',  3, 80).
personaje('Rin',   7, 120).
personaje('Alex',  4, 90).
personaje('Steve', 8, 150).
personaje('Raúl',  2, 60).

% ── HECHOS: Misiones (ID, Nombre, Dificultad, XP) ────────────
mision(m1, 'Bosque de Sombras', 2,  50).
mision(m2, 'Cueva del Dragón',  5, 120).
mision(m3, 'Torre Arcana',      7, 200).

% ── HECHOS: Inventarios ──────────────────────────────────────
inventario('Elara', [espada, escudo, pocion]).
inventario('Kael',  [arco, flechas]).
inventario('Rin',   [varita, grimorio, pocion, amuleto]).
inventario('Alex',  [daga, pocion]).
inventario('Steve', [hacha, escudo]).
inventario('Raúl',  [latigo, pocion]).

% ── HECHOS: Requerimientos de objetos por misión ─────────────
requiere(m2, escudo).
requiere(m2, pocion).
requiere(m3, grimorio).
requiere(m3, pocion).

% ── HECHOS: Enemigos (Nombre, Vida) ──────────────────────────
enemigo('Esqueleto',  30).
enemigo('Zombie',     80).
enemigo('Dragon',    200).

% ── HECHOS: Daño por arma ────────────────────────────────────
arma(espada,  25).
arma(arco,    15).
arma(varita,  30).
arma(daga,    10).
arma(hacha,   40).
arma(latigo,   5).

% ============================================================
% REGLAS — base (Taller5)
% ============================================================

% Nivel del personaje debe superar la dificultad de la misión.
puede_aceptar(Personaje, MisionID) :-
    personaje(Personaje, Nivel, _),
    mision(MisionID, _, Dificultad, _),
    Nivel >= Dificultad.

% XP acumulada tras N misiones: cada misión N aporta 30*N puntos.
% Patrón recursivo idéntico al factorial; caso base N=0.
xp_acumulada(0, 0).
xp_acumulada(N, Total) :-
    N > 0,
    N1 is N - 1,
    xp_acumulada(N1, Prev),
    Total is Prev + (30 * N).

% Unificación: comprueba si un objeto está en el inventario del personaje.
tiene_requerido(Personaje, Objeto) :-
    inventario(Personaje, Lista),
    member(Objeto, Lista).

% Busca un arma dentro del inventario y resuelve su daño.
tiene_arma(Personaje, Arma, Danio) :-
    inventario(Personaje, Objetos),
    member(Arma, Objetos),
    arma(Arma, Danio).

% Narrativa de ataque individual: usa if-then-else para decidir victoria/derrota.
ataque_individual(Personaje, Enemigo, MensajeFinal) :-
    enemigo(Enemigo, VidaEnemigo),
    tiene_arma(Personaje, Arma, Danio),
    VidaRestante is VidaEnemigo - Danio,
    (VidaRestante =< 0
     -> atomic_list_concat([Personaje, 'ataca con', Arma, 'haciendo', Danio, 'de daño y ELIMINA al', Enemigo], ' ', MensajeFinal)
     ;  atomic_list_concat([Personaje, 'ataca con', Arma, 'pero', Enemigo, 'sobrevive con', VidaRestante, 'de vida'], ' ', MensajeFinal)
    ).

% Suma el daño de cada personaje del grupo (toma la primera arma encontrada).
danio_grupo([], 0).
danio_grupo([P|Resto], TotalDanio) :-
    tiene_arma(P, _, DanioP), !,   % cut: evita backtracking a segunda arma
    danio_grupo(Resto, DanioResto),
    TotalDanio is DanioP + DanioResto.

% Narrativa del ataque combinado de un grupo.
ataque_grupal(Grupo, Enemigo, MensajeFinal) :-
    enemigo(Enemigo, VidaEnemigo),
    danio_grupo(Grupo, DanioTotal),
    VidaRestante is VidaEnemigo - DanioTotal,
    (VidaRestante =< 0
     -> atomic_list_concat(['VICTORIA: El equipo hizo', DanioTotal, 'de daño y ELIMINA al', Enemigo], ' ', MensajeFinal)
     ;  atomic_list_concat(['DERROTA: El equipo hizo', DanioTotal, 'de daño pero', Enemigo, 'sobrevive con', VidaRestante, 'vida'], ' ', MensajeFinal)
    ).

% Detecta pares de personajes con el mismo nivel (sin comparar un personaje consigo mismo).
mismo_nivel(P1, P2) :-
    personaje(P1, N, _),
    personaje(P2, N, _),
    P1 \== P2.

% Fusiona los inventarios de dos personajes en una sola lista.
fusionar_equipo(P1, P2, EquipoFusionado) :-
    inventario(P1, L1),
    inventario(P2, L2),
    append(L1, L2, EquipoFusionado).

% ── Conjugación para reportes narrativos ─────────────────────
tiempo(presente). tiempo(pasado). tiempo(futuro).
persona(primera). persona(segunda). persona(tercera).
numero(singular). numero(plural).

ser(presente, tercera, singular, "es").
ser(pasado,   tercera, singular, "fue").
ser(futuro,   tercera, singular, "será").
ser(presente, primera, singular, "soy").
ser(presente, primera, plural,   "somos").
ser(presente, tercera, plural,   "son").

conjugar_accion(Verbo, Tiempo, Persona, Numero, Conjugacion) :-
    tiempo(Tiempo), persona(Persona), numero(Numero),
    (Verbo = "ser"
     -> ser(Tiempo, Persona, Numero, R), Conjugacion = R
     ;  Conjugacion = Verbo
    ).

todos_pueden_aceptar([], _).
todos_pueden_aceptar([P|Resto], MisionID) :-
    puede_aceptar(P, MisionID),
    todos_pueden_aceptar(Resto, MisionID).

% Reporte para un solo personaje.
generar_reporte([Personaje], MisionID, Mensaje) :-
    puede_aceptar(Personaje, MisionID),
    mision(MisionID, Nombre, _, XP),
    conjugar_accion("ser", presente, tercera, singular, V),
    atomic_list_concat([Personaje, V, "capaz de completar", Nombre, "por", XP, "XP"], ' ', Mensaje).

% Reporte para un equipo de 2 o más personajes.
generar_reporte([P1, P2 | Resto], MisionID, Mensaje) :-
    Personajes = [P1, P2 | Resto],
    todos_pueden_aceptar(Personajes, MisionID),
    mision(MisionID, Nombre, _, XP),
    conjugar_accion("ser", presente, tercera, plural, V),
    atomic_list_concat(Personajes, ', ', NombresJuntos),
    atomic_list_concat([NombresJuntos, V, "asignados a la misión", Nombre, "por", XP, "XP"], ' ', Mensaje).

% ============================================================
% NUEVAS REGLAS — extensiones para la interfaz web
% ============================================================

% Umbral de élite: nivel >= 6 según el balance del juego.
% Separado del número fijo para que sea fácil de ajustar aquí.
es_experto(Personaje) :-
    personaje(Personaje, Nivel, _),
    Nivel >= 6.

% Suma el daño de TODAS las armas del personaje, no solo la primera.
% Útil para comparar potencial ofensivo sin simular un ataque concreto.
danio_total_personaje(Personaje, Total) :-
    findall(D, tiene_arma(Personaje, _, D), Danos),
    sum_list(Danos, Total).

% Lista todas las misiones que el personaje puede aceptar en este momento.
misiones_disponibles(Personaje, Misiones) :-
    findall(ID-Nombre, (mision(ID, Nombre, _, _), puede_aceptar(Personaje, ID)), Misiones).

% Verifica que el personaje lleve TODOS los objetos requeridos por la misión.
% Las misiones sin requerimientos (como m1) se aprueban automáticamente.
cumple_todos_requerimientos(Personaje, MisionID) :-
    findall(Obj, requiere(MisionID, Obj), Requeridos),
    (Requeridos = []
     -> true                          % sin requerimientos: cualquiera puede entrar
     ;  forall(member(Obj, Requeridos), tiene_requerido(Personaje, Obj))
    ).