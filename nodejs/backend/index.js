var express = require('express');
var app = express();

app.use(express.static('../public'));
var fs = require("fs");
app.get('/g', function (req, res) {
   res.send('Hello World');
});
var multer  = require('multer');
const upload = multer({ dest: '/tmp/' })
app.use(express.urlencoded({ extended: false }));
app.use(upload.any());

app.post('/upload',  function (req, res) {
    var file = __dirname + "/" + req.files[0].originalname;
    console.log(req.files[0]);
    fs.readFile( req.files[0].path, function (err, data) {
       fs.writeFile(file, data, function (err) {
           let response = {};
          if( err ) {
             console.log( err );
             } else {
                response = {
                   message:'File uploaded successfully',
                   filename:req.files[0].originalname
                };
             }
          
          console.log( response );
          res.end( JSON.stringify( response ) );
       });
    });
 })



var server = app.listen(8081, function () {
   var host = server.address().address
   var port = server.address().port
   
   console.log("Example app listening at http://%s:%s", host, port)
})