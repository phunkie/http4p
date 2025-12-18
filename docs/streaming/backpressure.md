# Backpressure

Backpressure is the ability of a consumer to signal a producer to slow down. In `http4p` and `phunkie/streams`, backpressure is implicit and built-in via the **Pull-based** architecture.

## How it works

1.  **Lazy Evaluation**: Streams are lazy. No data is read from the source (File, DB, Socket) until the consumer asks for it.
2.  **Chunk-by-Chunk**: Data is processed in discrete chunks.
3.  **Synchronous blocking**: In the standard PHP execution model, writing to the output buffer (`echo`) blocks if the buffer is full or the client is slow to receive.
    -   When `PhpServer` writes a chunk, `echo` blocks.
    -   This blocks the `drain()` loop.
    -   This blocks the `pull()` from the source.
    -   Therefore, we stop reading from the source until the client is ready.

This ensures that we never read more data into memory than what we can send, preventing Out-Of-Memory errors even when streaming gigabytes of data to a slow client.
