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
- [Streaming](core/streaming.md) - Backpressure, cancellation, and memory efficiency

### Routing
- [Route Definition](routing/definition.md) - Defining routes with `GET`, `POST`, etc.
- [Path Parameters](routing/path-parameters.md) - Type-safe parameter extraction
- [Request Handling](routing/request-handling.md) - Working with `Request` objects
- [Response Construction](routing/response-construction.md) - Using `Ok`, `Created`, `NotFound`, etc.

### Streaming
- [File Streaming](streaming/files.md) - Serving large files efficiently
- [Database Streaming](streaming/database.md) - Streaming query results
- [Custom Streams](streaming/custom.md) - Building custom streaming responses
- [Backpressure](streaming/backpressure.md) - Handling slow clients

### Middleware
- [Middleware Basics](middleware/basics.md) - Composing middleware
- [Built-in Middleware](middleware/built-in.md) - Logger, Auth, CORS, etc.
- [Custom Middleware](middleware/custom.md) - Writing your own middleware

### Client
- [HTTP Client](client/basics.md) - Making HTTP requests
- [Request Building](client/requests.md) - Fluent request API
- [Response Handling](client/responses.md) - Processing responses
- [Streaming Responses](client/streaming.md) - Handling streaming responses

### Server
- [Server Configuration](server/configuration.md) - Configuring the server
- [Built-in Server](server/built-in.md) - Using PHP's built-in server
- [Production Deployment](server/production.md) - Deploying with FPM, ReactPHP, etc.

### Advanced
- [Effect Integration](advanced/effects.md) - Working with IO effects
- [Parallel Requests](advanced/parallel.md) - Concurrent request handling
- [Error Handling](advanced/errors.md) - Functional error handling
- [Testing](advanced/testing.md) - Testing Http4p applications

### Examples
- [REST API](examples/rest-api.md) - Building a complete REST API
- [File Upload/Download](examples/files.md) - Handling file operations
- [WebSocket Proxy](examples/websocket.md) - Proxying WebSocket connections
- [Microservices](examples/microservices.md) - Building microservices

### API Reference
- [Functions](api/functions.md) - All exported functions
- [Types](api/types.md) - Type definitions
- [Response Constructors](api/responses.md) - Complete response constructor reference
