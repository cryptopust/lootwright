# ADR 0001: Laravel Modular Monolith

- Status: Accepted
- Date: 2026-08-14

## Context

Lootwright needs clear domain boundaries, background work, operational visibility, and a modern web interface. The MVP does not have independently scaling teams or workloads that justify distributed services.

## Decision

Build one Laravel 13 modular monolith with Inertia 3, Vue 3 Composition API, TypeScript, Tailwind CSS, shadcn-vue, PostgreSQL, Redis, and Laravel Horizon.

Keep framework-independent domain and analysis packages under `src/`, Laravel delivery/infrastructure under `app/Modules`, and presentation under `resources/js`. Modules communicate through typed public ports and own their persistence. HTTP and Horizon workers ship from the same repository and release artifact.

## Consequences

- Local development, testing, transactions, deployment, and policy enforcement remain coherent.
- Boundaries are enforced by namespaces, dependency tests, module APIs, and review rather than network calls.
- A slow or risky task may run through Horizon without becoming a service.
- Microservices, Kafka, Kubernetes, and event sourcing are excluded from the MVP.
- Splitting a service later requires measured need and a superseding ADR.

