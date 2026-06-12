# Sistema de Consultas Prolog — Juego de Roles

Materia: Lenguajes de Programación | Periodo: 2026-1 | Estado: Completado

## Equipo de trabajo

- [Raúl Alejandro Garay Vinueza](https://github.com/raulgaray26)

## Capturas de Pantalla (Demo)
![Vista Principal](/docs/screenshots/vista_principal.png)
![Consultas](/docs/screenshots/consultas_disponibles_1.png)
![Consultas](/docs/screenshots/consultas_disponibles_2.png)
![Listado](/docs/screenshots/listado_personajes.png)
![Regla](/docs/screenshots/regla_nueva_1.png)
![Regla](/docs/screenshots/regla_nueva_2.png)
![Regla](/docs/screenshots/regla_nueva_3.png)
![Regla](/docs/screenshots/regla_nueva_4.png)

## Funcionalidad

- [x] Base de conocimiento extendida en Prolog: personajes, misiones, enemigos, inventarios y 4 reglas nuevas [Commit](https://github.com/raulgaray26/prolog-game/commit/52c753a67d40647d700b60a09b420fcc82fb1f7b)
- [x] `PrologService`: puente PHP → `swipl` para ejecutar goals directamente desde Laravel [Commit](https://github.com/raulgaray26/prolog-game/commit/77ec5018d332512579aabb510126632e0d1acbd3)
- [x] Interfaz web con 15 tipos de consulta interactivas agrupadas por categoría [Commit](https://github.com/raulgaray26/prolog-game/commit/c5168efdcefbba81ba22f244167b08501223a6e5)

## Tecnologías

`PHP` | `Laravel` | `SWI-Prolog` | `Bootstrap 5` | `Blade Templates`

## Ejecución

### Prerequisitos

- PHP >= 8.2 con la función `exec()` habilitada
- [Composer](https://getcomposer.org/)
- SWI-Prolog instalado y `swipl` disponible en PATH:
  - **Ubuntu/Debian:** `sudo apt install swi-prolog`
  - **macOS:** `brew install swi-prolog`
  - **Windows:** [swi-prolog.org/download/stable](https://www.swi-prolog.org/download/stable)
- Git

### Instalación

```bash
git clone https://github.com/raulgaray26/prolog-game.git
cd prolog-game
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Abre `http://localhost:8000` en el navegador.

> **Verificar swipl:** ejecuta `swipl --version` en la terminal. Si el comando no se reconoce, revisa la instalación o añade la ruta al PATH del sistema.

## Métricas de Progreso

| Indicador | Valor |
|---|---|
| Commits totales | 8 |
| Issues/PRs fusionados | 3 / 5 |
| Cobertura de pruebas | N/A |
| Última actualización | 2026-06-12 |

## Reflexión y Aprendizajes

- **Habilidades desarrolladas:** Integración del paradigma lógico (Prolog) con el paradigma web (Laravel MVC); diseño de servicios desacoplados en PHP; comunicación inter-proceso mediante.
- **Qué funcionó bien:** Separar toda la lógica de Prolog en `PrologService` permitió agregar nuevas consultas sin tocar la vista ni el controlador. Usar if-then-else dentro de los goals garantiza siempre una respuesta legible, aunque el predicado falle.
- **Qué se podría mejorar:** sería mejor una conexión persistente a SWI-Prolog. También falta validación de entrada para prevenir inyección de código Prolog malicioso.
- **Conceptos clave aplicados de la materia:** Unificación y backtracking; reglas recursivas; operadores relacionales; procesamiento de listas con `findall/3` y `forall/2`; if-then-else en Prolog; integración de paradigmas lógico y orientado a objetos.
