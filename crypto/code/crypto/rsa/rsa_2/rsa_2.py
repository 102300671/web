from Crypto.Util.number import getPrime,bytes_to_long
from flag import flag

p = getPrime(128)
q = getPrime(128)
n = p * q
e = 65537
c = pow(bytes_to_long(flag), e, n)
print("n = ", n)
print("e = ", e)
print("c = ", c)