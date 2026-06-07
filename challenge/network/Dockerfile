FROM ubuntu:22.04

# python3-scapy generates the pcap at build time; tcpdump lets students inspect
# it inside the instance; ttyd exposes the shell over HTTP.
RUN apt-get update \
    && apt-get install -y --no-install-recommends python3 python3-scapy tcpdump ttyd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /challenge
COPY gen_pcap.py .
COPY README.txt .

# Produce challenge.pcap, then delete the generator (it contains the flag).
RUN python3 gen_pcap.py && rm gen_pcap.py

EXPOSE 7681
CMD ["sh", "-c", "timeout 1800 ttyd -p 7681 -W bash"]
