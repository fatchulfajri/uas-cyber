FROM ubuntu:22.04

# python3 to generate the RSA output at build time, ttyd for the instance shell.
RUN apt-get update \
    && apt-get install -y --no-install-recommends python3 ttyd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /challenge
COPY gen_rsa.py .
COPY README.txt .

# Produce output.txt, then delete the generator (it contains the plaintext flag).
RUN python3 gen_rsa.py && rm gen_rsa.py

EXPOSE 7681
CMD ["sh", "-c", "timeout 1800 ttyd -p 7681 -W bash"]
