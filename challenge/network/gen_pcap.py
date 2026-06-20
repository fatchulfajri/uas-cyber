#!/usr/bin/env python3
"""Build challenge.pcap simulating data exfiltration over ICMP.

Each character of the flag is hidden in the payload of a single ICMP echo
request (type 8). Benign echo replies (type 0) are interleaved as noise so it
looks like an ordinary ping session. Read the echo-request payloads in order to
recover the flag.

Runs at image build time and is then deleted, so the plaintext flag below never
ships inside the instance.
"""
from scapy.all import IP, ICMP, Raw, wrpcap

FLAG = "CTF{f0ll0w_th3_tcp_str34m_sh4rk}"
SRC = "10.0.0.13"   # the "compromised" host doing the exfiltration
DST = "10.0.0.1"    # the attacker's collector

packets = []
for seq, ch in enumerate(FLAG, start=1):
    # One flag byte per ping request payload.
    req = IP(src=SRC, dst=DST) / ICMP(type=8, id=0x1337, seq=seq) / Raw(load=ch.encode())
    packets.append(req)
    # A matching reply with innocuous padding to blend in.
    rep = IP(src=DST, dst=SRC) / ICMP(type=0, id=0x1337, seq=seq) / Raw(load=b"pong")
    packets.append(rep)

wrpcap("challenge.pcap", packets)
