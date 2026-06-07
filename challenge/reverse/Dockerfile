FROM ubuntu:22.04

# gcc to compile the challenge in-image (no binary committed to the repo),
# ttyd to expose an interactive shell over HTTP for the spawned instance.
RUN apt-get update \
    && apt-get install -y --no-install-recommends gcc libc6-dev ttyd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /challenge
COPY chall.c .
COPY README.txt .

# Compile stripped (-s) so it's a proper reverse-engineering target, then remove
# the source so the only artifact is the binary.
RUN gcc -O2 -s -o chall chall.c && rm chall.c

EXPOSE 7681
# Self-destruct after 30 min; ttyd serves a writable bash shell in /challenge.
CMD ["sh", "-c", "timeout 1800 ttyd -p 7681 -W bash"]
