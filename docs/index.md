# Http4p Documentation

## Table of Contents

### Getting Started
- [Installation](getting-started/installation.md)
- [Quick Start](getting-started/quick-start.md)
- [Core Concepts](getting-started/core-concepts.md)

### Core Concepts
- [Response Model](core/response-model.md) - Understanding `Response<F>` and `Stream<F, Byte>`
- [Type Signatures](core/type-signatures.md) - `IO<Response<IO>>` explained
- [Entity Encoding](core/entity-encoding.md) - Converting values to streaming bodies
- [Entity Decoding](core/entity-decoding.md) - Handling request bodies (Not Middleware!)
- [Streaming](core/streaming.md) - Backpressure, cancellation, and memory efficiency
- [Client](core/client.md) - Calling another service and consuming its response as a stream

### Routing
- [Route Definition](routing/definition.md) - Defining routes with `GET`, `POST`, etc.
- [Path Parameters](routing/path-parameters.md) - Type-safe parameter extraction
- [Request Handling](routing/request-handling.md) - Working with `Request` objects
- [Response Construction](routing/response-construction.md) - Using `Ok`, `Created`, `NotFound`, etc.

### Streaming
- [Overview](core/streaming.md) - **Start Here**: How Streaming works in Http4p
- [File Streaming](streaming/files.md) - Serving and receiving files
- [Database Streaming](streaming/database.md) - Streaming query results
- [Custom Streams](streaming/custom.md) - Building custom streams
- [Backpressure](streaming/backpressure.md) - Implicit flow control
- [Client](core/client.md) - Reading a streamed response, decoding an export line by line

### Middleware
- [Basics](middleware/basics.md) - How usage works
- [Built-in](middleware/built-in.md) - Logger, Cors
- [Custom](middleware/custom.md) - Writing your own

### Examples
- [REST API](examples/rest-api.md) - Building a complete REST API

### API Reference
- [Functions](api/functions.md)
- [Types](api/types.md)

