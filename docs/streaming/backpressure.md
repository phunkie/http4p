# Backpressure

Backpressure is the ability of a consumer to signal a producer to slow down. In `http4p` and `phunkie/streams` it is implicit: the pull-based pipeline reads nothing until the consumer asks for it, and the consumer is the socket.

## How it works

1.  **Lazy evaluation**: a stream reads from its source (file, database statement, socket) only when compiled, one element at a time.
2.  **One chunk in flight**: `PhpServer` pulls a chunk, writes it, flushes it, and only then pulls the next; nothing is buffered in between.
3.  **The write blocks**: when the client is slow to receive, the flush blocks until the SAPI can send.
    -   This blocks the `drain()` loop.
    -   This blocks the pull from the source.
    -   So the source is not read faster than the client takes the data.

The same holds on the client side: `send` gives a body that reads from the connection as it is compiled, so a producer that is slow to send slows the consumer down, and a consumer that processes slowly leaves the data in the socket.

Memory stays at one chunk on both sides whatever the size of the body and the speed of either end.
