# ADR 0008: No Client or Browser Integration

- Status: Accepted
- Date: 2026-08-14

## Context

Client inspection, overlays, browser extensions, macros, clipboard readers, and automation create security, malware, policy, and account-ban risk. Lootwright can deliver its core value from explicit text input in a normal website.

## Decision

Lootwright is web-only. It will not ship or control an executable, overlay, injected component, browser extension, game/client integration, file/log watcher, process/memory reader, screen/clipboard reader, packet/network inspector, keyboard/mouse automation, gameplay/chat/trade automation, or browser automation.

The application accepts only material the user deliberately submits through its own UI. No interface or abstraction may be added "for future" client or browser control.

## Consequences

- The trust and compliance boundary is smaller and easier to explain.
- Users paste inputs and perform Trade steps manually.
- Features that require ambient game/browser state are rejected, not deferred.
- Reversing this decision would change the product category and requires a new constitution, legal/policy review, and a superseding ADR.

