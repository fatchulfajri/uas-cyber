#!/usr/bin/env python3
"""Generate a deliberately weak RSA challenge.

The modulus n is the product of two 24-bit primes, so it factors in a fraction
of a second (trial division, factordb, sympy, etc.). Recovering p and q gives
phi, then d = e^-1 mod phi, and the flag decrypts.

The flag is longer than n, so it is split into small big-endian blocks and each
block is encrypted separately; output.txt contains n, e and the list of
ciphertext blocks.

This script runs at image build time and is then deleted, so the plaintext flag
below never ships inside the instance.
"""
import random

FLAG = b"CTF{crypt0_c4es4r_b4se64}"
E = 65537
BLOCK = 4  # bytes per block; max block value 2^32 stays well below n (>= 2^46)


def is_prime(x: int) -> bool:
    if x < 2:
        return False
    if x % 2 == 0:
        return x == 2
    i = 3
    while i * i <= x:
        if x % i == 0:
            return False
        i += 2
    return True


def gen_prime_24() -> int:
    while True:
        cand = random.randint(1 << 23, (1 << 24) - 1) | 1
        if is_prime(cand):
            return cand


def egcd(a: int, b: int):
    if b == 0:
        return a, 1, 0
    g, x, y = egcd(b, a % b)
    return g, y, x - (a // b) * y


def modinv(a: int, m: int) -> int:
    g, x, _ = egcd(a % m, m)
    if g != 1:
        raise ValueError("no modular inverse")
    return x % m


def main() -> None:
    while True:
        p, q = gen_prime_24(), gen_prime_24()
        if p == q:
            continue
        phi = (p - 1) * (q - 1)
        if phi % E == 0:
            continue
        try:
            modinv(E, phi)  # ensure e is invertible mod phi
        except ValueError:
            continue
        break

    n = p * q
    blocks = [FLAG[i:i + BLOCK] for i in range(0, len(FLAG), BLOCK)]
    cipher = [pow(int.from_bytes(b, "big"), E, n) for b in blocks]

    with open("output.txt", "w") as f:
        f.write(f"n = {n}\n")
        f.write(f"e = {E}\n")
        f.write(f"c = {cipher}\n")

    # p, q, d are intentionally NOT written — recovering them is the challenge.


if __name__ == "__main__":
    main()
