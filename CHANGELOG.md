# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [v1.1.0] - 2025-11-03

### Changed
- Github actions are added.
- RequestFactory::fromArray() now creates a batch request if the input array contains multiple items.
- Server::executePsrRequest() now returns null if the request is a notification.
- Server::executeArrayRequest() now returns null if the request is a notification.

## [v1.0.2] - 2025-10-30

### Changed
- Remove the generated static files for documentation and coverage.

## [v1.0.1] - 2025-10-30

### Changed
- Response DTO: make `id` non-readonly and set it after executing the procedure to ensure response contains the correct request id.

### Documentation
- Update generated coverage and docs.
- Add `.phpdoc/` to `.gitignore`.

## [v1.0.0] - 2025-10-28
- Initial tagged release.

[v1.1.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.1.0
[v1.0.2]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.0.2
[v1.0.1]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.0.1
[v1.0.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.0.0
