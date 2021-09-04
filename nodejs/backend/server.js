const http = require('http')
const crypto = require('crypto')

const {
    publicKey,
    privateKey,
} = crypto.generateKeyPairSync('rsa', {
    modulusLength: 4096,
    publicKeyEncoding: {
        type: 'spki',
        format: 'der'
    },
    privateKeyEncoding: {
        type: 'pkcs8',
        format: 'der',
        cipher: 'aes-256-cbc',
        passphrase: ''
    }
});
const server = http.createServer(function(req){
    console.log(privateKey);
});
server.listen(8080,'localhost')