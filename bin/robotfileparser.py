# @author SATO Kentaro
# @license BSD 2-Clause License

import struct
import sys
import urllib.robotparser

rp = urllib.robotparser.RobotFileParser('http://www.example.com')

while True:
    try:
        header = struct.unpack('>3l', sys.stdin.buffer.read(4 * 3))
        text = ua = path = ''
        # urllib.robotparser internally treats external robots.txt as UTF-8
        if header[0]:
            text = sys.stdin.buffer.read(header[0]).decode('utf-8')
        if header[1]:
            ua = sys.stdin.buffer.read(header[1]).decode('utf-8')
        if header[2]:
            path = sys.stdin.buffer.read(header[2]).decode('utf-8')
        rp.parse(text.splitlines())
        print('1' if rp.can_fetch(ua, 'http://www.example.com' + path) else '0')
        print("<<<END>>>", file=sys.stderr)
        sys.stdout.flush()
        sys.stderr.flush()
    except EOFError:
        break
