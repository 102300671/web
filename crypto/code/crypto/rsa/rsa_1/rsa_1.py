from Crypto.Util.number import getPrime, bytes_to_long
from flag import flag

p = getPrime(512)
q = getPrime(512)
n = p * q
e = 65537
c = pow(bytes_to_long(flag),e,n)
print("p = ", p)
print("q = ", q)
print("e = ", e)
print("c = ", c)