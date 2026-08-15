# Private Evaluation Fixtures

Only fixtures deliberately authorized by their user may be placed here. Every
other file in this directory is ignored by Git. Never stage, commit, upload, or
paste a private fixture into an issue, report, screenshot, or log. Structural
suite fixtures are local-only and are never sent to an AI provider.

Private cases use the same JSON envelope as `evals/cases/extended.json`, must
set `user_authorized` to `true`, and run only with
`php artisan eval:run --suite=extended --include-private`. Reports replace the
case ID with a one-way hash and contain no raw input. Delete the fixture after
the authorized review window ends.

The separate, default-off live-provider command accepts only a purpose-built
descriptor containing a short natural-language description, not a PoB, item
text, note collection, or structural case envelope:

```json
{
  "user_authorized": true,
  "provider_processing_authorized": true,
  "description": "Short authorized description"
}
```

It additionally requires `--allow-private`, redacts common personal/secret
patterns, and reports only a hashed file reference and usage metadata. Prefer
the command's built-in synthetic description; delete any authorized descriptor
immediately after its review window.
