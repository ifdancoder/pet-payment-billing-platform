# Architecture Decision Records

An ADR is a short writeup of one significant decision: what forced it,
what was decided, what it costs. Write it once, when the decision is
made, and don't go back and edit it later. If the decision stops
holding, write a new ADR that supersedes the old one instead of touching
it.

## When to write one

Write an ADR for anything hard to reverse, anything that touches more
than one service, or anything a future contributor (including future
you) would otherwise have to reverse-engineer from the code. Think:
choice of message broker, database-per-service boundaries, sync vs async
for a given workflow, which ingress controller or operator to use under
`infrastructure/kubernetes/`.

Day-to-day stuff that's easy to change later doesn't need one.

## Format

One file per decision, named `NNNN-short-title.md` (zero-padded,
sequential), roughly the classic Nygard template:

```markdown
# NNNN. Title

## Status

Proposed | Accepted | Superseded by NNNN

## Context

What's forcing this decision: technical, business, team constraints.
State the facts, not the decision.

## Decision

What was decided, in plain active voice.

## Consequences

What gets easier, what gets harder. Trade-offs and follow-up work, not
just the upside.
```

## Recorded

- [0001. API gateway routing and the public/internal split](0001-api-gateway-routing.md)
