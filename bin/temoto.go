/// @author SATO Kentaro
/// @license BSD 2-Clause License

package main

import "bufio"
import "encoding/binary"
import "fmt"
import "io"
import "os"
import "github.com/temoto/robotstxt"

func main() {
	stdin := bufio.NewReader(os.Stdin)
	stdout := bufio.NewWriter(os.Stdout)
	stderr := bufio.NewWriter(os.Stderr)
	header := make([]byte, 4*3)
	i := 0
	for {
		i++
		n, err := stdin.Read(header)
		if n != 12 || err == io.EOF {
			break
		}
		text := readData(stdin, header[0:4], stderr)
		ua := string(readData(stdin, header[4:8], stderr))
		path := string(readData(stdin, header[8:12], stderr))
		var allowed bool
		if len(text) <= 1024 * 500 {
			tester, err := robotstxt.FromBytes(text)
			if err == nil {
				allowed = tester.TestAgent(path, ua)
			} else {
				allowed = false
				stderr.Write([]byte(err.Error()))
			}
		} else {
			allowed = false
			stderr.Write([]byte("Input is too big to process")) // temoto/robotstxt stalls on big line
		}
		if allowed {
			stdout.Write([]byte("1\n"))
		} else {
			stdout.Write([]byte("0\n"))
		}
		stdout.Flush()
		stderr.Write([]byte("\n<<<END>>>\n")) // pre-\n is required
		stderr.Flush()
	}
	stdout.Flush()
	stderr.Flush()
}

func readData(stdin *bufio.Reader, rawLength []byte, stderr *bufio.Writer) []byte {
	len := int(binary.BigEndian.Uint32(rawLength))
	if len <= 0 {
		return []byte{}
	}
	data := make([]byte, len)
	offset := 0
	for {
		n, _ := stdin.Read(data[offset:])
		offset += n
		if offset >= len {
			break
		}
	}
	if offset != len {
		stderr.Write([]byte(fmt.Sprintf("Requested %d bytes but got %d bytes\n", len, offset)))
		stderr.Flush()
		os.Exit(1)
	}
	return data
}
