<?php
$token = ""; //Ganti dengan Token yang diperoleh dari BotFather
$usernamebot="@mantengaran_bot"; //nama bot yang diperoleh dari BotFather
define('BOT_TOKEN', $token); 

define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

$debug = false;

function exec_curl_request($handle)
{
    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error\n");
        curl_close($handle);

        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);

    if ($http_code >= 500) {
        // do not wat to DDOS server if something goes wrong
    sleep(10);

        return false;
    } elseif ($http_code != 200) {
        $response = json_decode($response, true);
        error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
        if ($http_code == 401) {
            throw new Exception('Invalid access token provided');
        }

        return false;
    } else {
        $response = json_decode($response, true);
        if (isset($response['description'])) {
            error_log("Request was successfull: {$response['description']}\n");
        }
        $response = $response['result'];
    }

    return $response;
}

function apiRequest($method, $parameters = null)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");

        return false;
    }

    if (!$parameters) {
        $parameters = [];
    } elseif (!is_array($parameters)) {
        error_log("Parameters must be an array\n");

        return false;
    }

    foreach ($parameters as $key => &$val) {
        // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
        $val = json_encode($val);
    }
    }
    $url = API_URL.$method.'?'.http_build_query($parameters);

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

    return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");

        return false;
    }

    if (!$parameters) {
        $parameters = [];
    } elseif (!is_array($parameters)) {
        error_log("Parameters must be an array\n");

        return false;
    }

    $parameters['method'] = $method;

    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    return exec_curl_request($handle);
}

// jebakan token, klo ga diisi akan mati
if (strlen(BOT_TOKEN) < 20) {
    die(PHP_EOL."-> -> Token BOT API nya mohon diisi dengan benar!\n");
}

function getUpdates($last_id = null)
{
    $params = [];
    if (!empty($last_id)) {
        $params = ['offset' => $last_id + 1, 'limit' => 1];
    }
  //echo print_r($params, true);
  return apiRequest('getUpdates', $params);
}

// matikan ini jika ingin bot berjalan
//die('baca dengan teliti yak!');

// ----------- pantengin mulai ini
function sendMessage($idpesan, $idchat, $pesan)
{
    $data = [
    'chat_id'             => $idchat,
    'text'                => $pesan,
    'parse_mode'          => 'Markdown',
    'reply_to_message_id' => $idpesan,
  ];

    return apiRequest('sendMessage', $data);
}

function processMessage($message)
{
    global $database;
    if ($GLOBALS['debug']) {
        print_r($message);
    }

    if (isset($message['message'])) {
            
        $sumber = $message['message'];
        $idpesan = $sumber['message_id'];
        $idchat = $sumber['chat']['id'];
        
        $username = $sumber["from"]["username"];
        $nama = $sumber['from']['first_name'];
        $iduser = $sumber['from']['id'];

        if (isset($sumber['text'])) {
            $pesan = $sumber['text'];

            if (preg_match("/^\/view_(\d+)$/i", $pesan, $cocok)) {
                $pesan = "/view $cocok[1]";
            }

            if (preg_match("/^\/hapus_(\d+)$/i", $pesan, $cocok)) {
                $pesan = "/hapus $cocok[1]";
            }

     // print_r($pesan);

      $pecah2 = explode(' ', $pesan, 3);
            $katake1 = strtolower($pecah2[0]); //untuk command
            $katake2 = strtolower($pecah2[1]); // kata pertama setelah command
            $katake3 = strtolower($pecah2[2]); // kata kedua setelah command
            
      $pecah = explode(' ', $pesan, 2);
            $katapertama = strtolower($pecah[0]); //untuk command
            
        switch ($katapertama) {
        case '/start': 
		case '/start@mantengaran_bot':
          $text = "Selamat datang..., `$nama`! Untuk bantuan ketik: /help";
          break;

        case '/help': 
        case '/help@mantengaran_bot':
          $text = "Berikut menu yang tersedia:\n\n";
		   $text .= "/help info bantuan\n\n";	
          $text .= "Ketik /kelulusan spasi `nomor_peserta_um` spasi `password_um` lalu kirim untuk melihat status kelulusan siswa\n\n";
          /*
          $text .= "/registrasi `nohp` untuk registrasi user baru\n";
          $text .= "/password `passwordbaru` untuk ganti password\n";
          $text .= "/username `usernamebaru` untuk ganti username\n";
          $text .= "/login `username` `password` untuk login\n";
          $text .= "/myakun untuk melihat akun Anda\n"; 
          */
          $text .= "ketik /time lalu kirim untuk melihat info waktu sekarang.";
          break; 
		  
        case '/time': 
		case '/time@mantengaran_bot':
          date_default_timezone_set("Asia/Jakarta");
		  $waktusekarang = date('d-m-Y H:i:s');
          $text = "Waktu Sekarang: $waktusekarang\n";
		  $text .= "Jadwal shalat: http://blogchem.com/shalat/widget.html";
          break;		  
          
		case '/registrasi':
        case '/registrasi@mantengaran_bot': 
				if (isset($pecah[1])) {
					$nohp = $pecah[1]; //mendapatkan nohp dari kata kedua
					$password = rand(111111, 999999); //contoh untuk mendapatkan password awal secara random
				  
					include "koneksi.php";
					
					if(mysqli_num_rows(mysqli_query($mysqli, "select * from registrasi where username='".mysqli_real_escape_string($username)."'"))){
							$text = "Anda sudah terdaftar sebelumnya dengan username: $username";
					} else
					{ 
						$nama = str_replace("'","",$nama); //salah satu cara menghilangkan tanda petik
						$username = str_replace("'","",$username);
						$password = str_replace("'","",$password);
						
						$simpan="INSERT INTO registrasi SET 
							iduser='$iduser', 
							nama='$nama', 
							nohp = '$nohp',
							username='$username',
							password='$password'";  
									
							mysqli_query($mysqli, $simpan); 		  
							  
							$text = "Selamat $nama ($iduser), registrasi Saudara berhasil, Username: $username, Password: $password\n\n";
							$text .= "Silahkan login ke https://wfh.man2semarang.sch.id menggunakan username=$username dan password=$password\n";
							$text .= "Ganti password: /password `passwordbaru`\n";	
							
						//include "sendMessage.php"; 
					}
				} else {
				  $text = '*ERROR:* _No HP tidak boleh kosong!_';
				  $text .= "\n";
				  $text .= "Format: /registrasi `nomor HP`";
				}
		break;
		case '/kelulusan':
        case '/kelulusan@mantengaran_bot': 
            $tanggal = '2020-05-02 17:00:00';
            $currentTime = time();
            if ($currentTime > strtotime($tanggal)) 
            {
				if ((isset($pecah2[1])) and (isset($pecah2[2])))
				{
					$nomor_um = $pecah2[1]; //mendapatkan nohp dari kata kedua
					$password = $pecah2[2]; //contoh untuk mendapatkan password awal secara random
				    $nomor_um = str_replace("'","",$nomor_um);
				    $password = str_replace("'","",$password);
					include "koneksi.php";
					if(mysqli_num_rows(mysqli_query($mysqli, "select * from `kelulusan` where `nomor_um` ='$nomor_um' and `password` ='$password'")))
					{
					        $ta = mysqli_query($mysqli, "select * from `kelulusan` where `nomor_um` ='$nomor_um' and `password` ='$password'");
					        $nama_siswa = 'nama tidak dikenal';
					        $d = mysqli_fetch_array($ta);
					        $nama_siswa = $d['nama'];
							$text = 'Dear '.$nama.', selamat Ananda *'.$nama_siswa.'* dinyatakan *LULUS*';
							mysqli_query($mysqli,"UPDATE `kelulusan` SET `dilihat` = '1' WHERE `nomor_um`='$nomor_um'");
					} else
					{ 
					    $text = "Maaf, $nama , data kelulusan yang dimaksud tidak kami temukan, pastikan nomor peserta dan password benar";
					}

				} else {
				  $text = '*ERROR:* _No peserta um atau password tidak boleh kosong!_';
				  $text .= "\n";
				  $text .= "Format: /kelulusan `001.19.20.363.209` `123456`";
				}
            }
    		else
	    	{
	    	    $nama_siswa = '';
	    	    $text = '';
				if ((isset($pecah2[1])) and (isset($pecah2[2])))
				{
					$nomor_um = $pecah2[1]; //mendapatkan nohp dari kata kedua
					$password = $pecah2[2]; //contoh untuk mendapatkan password awal secara random
				    $nomor_um = str_replace("'","",$nomor_um);
				    $password = str_replace("'","",$password);
    				include "koneksi.php";
    	    	    if(mysqli_num_rows(mysqli_query($mysqli, "select * from `kelulusan` where `nomor_um` ='$nomor_um' and `password` ='$password'")))
					{
					        $ta = mysqli_query($mysqli, "select * from `kelulusan` where `nomor_um` ='$nomor_um' and `password` ='$password'");
					        $nama_siswa = 'nama tidak dikenal';
					        $d = mysqli_fetch_array($ta);
					        $nama_siswa = $d['nama'];
					}
					else
					{ 
					    $text = "Maaf, $nama , data kelulusan yang dimaksud tidak kami temukan, pastikan nomor peserta dan password benar\n\n";
					}
				}
				else
	    	{
	    	     $text = '*ERROR:* _No peserta um atau password tidak boleh kosong!_';
				  $text .= "\n";
				  $text .= "Format:\n /kelulusan `001.19.20.363.209` `123456`\n\n";
	    	}
					if(!empty($nama_siswa))
					{
					    $text = "Assalamu alaikum, wr.wb. Ananda ".$nama_siswa.",\n\n";
					}
    		    $text .= '*INFO:* _Saat ini belum waktunya kelulusan_. *Kelulusan* akan diumumkan _'.$tanggal.'_';
		    }
		break;          
        case '/password':
        case '/password@mantengaran_bot':
          if (isset($pecah[1])) {
                $password = $pecah[1];
                include 'koneksi.php';

		        $simpan="UPDATE registrasi SET 
			    password='$password' where iduser='$iduser'";
		        mysqli_query($mysqli, $simpan); 
                
                $text = "$nama ($iduser), password telah berhasil diganti!";
          } else {
              $text = '*ERROR:* _Password pengganti tidak boleh kosong!_';
			  $text .= "\n";
			  $text .= "Format: /password `passwordbaru`";
          }
          break;     
          
        case '/username':
        case '/username@mantengaran_bot':
          if (isset($pecah[1])) {
                $username = $pecah[1];
                include 'koneksi.php';

		        $simpan="UPDATE registrasi SET 
			    username='$username' where iduser='$iduser'";
		        mysqli_query($mysqli, $simpan); 
                
                $text = "$nama ($iduser), username telah berhasil diganti!";
          } else {
              $text = '*ERROR:* _Username pengganti tidak boleh kosong!_';
          }
          break;     
          
        case '/myakun':
        case '/myakun@mantengaran_bot':
            	include 'koneksi.php';
        			
        		$tampil="select * from registrasi WHERE iduser='$iduser'"; 
        		$qryTampil=mysqli_query($mysqli, $tampil); 
        		if(mysqli_num_rows($qryTampil) > 0)
        		{
        		$data=mysqli_fetch_array($qryTampil);
				
        		$nama = $data['nama']; 
				$nohp=$data['nohp'];				
        		$username = $data['username'];
				$password = $data['password'];

        		$text = "Akun anda adalah \nNama: $nama,  \nNo. HP: $nohp, \nUsername: $username, \nPassword: $password.\n\n";
				$text .= "Login https://wfh.man2semarang.sch.id"; 
        		}
        		else
        		{
        		 	$text = "Anda tidak punya akun";    
        		}
        break;  
        
        case '/login':
        case '/login@mantengaran_bot':
          if (isset($pecah2[1])) {
                $username = $pecah2[1]; //mendapatkan kata kedua setelah command
                $password = $pecah2[2]; //mendapatkan kata ketiga setelah command
                
                $text .= "Klik link http://wfh.man2semarang.sch.id"; 
          } else {
              $text = '*ERROR:* _Username dan Password tidak boleh kosong!_';
			  $text .= "\n";
			  $text .= "Format: /login `username` `password`";
          }
          break;        

        default:
          $text = '_Maaf, saya tidak mengerti yang Anda maksud_';
		  $text .= "\n";
		  $text .= "Klik /help untuk bantuan";
          break;
      }
        } else {
            $text = 'Silahkan tulis pesan yang akan disampaikan..';
			$text .= "\n";
			$text .= "Format: /pesan `pesan`";
        }

        $hasil = sendMessage($idpesan, $idchat, $text);
        if ($GLOBALS['debug']) {
            // hanya nampak saat metode poll dan debug = true;
      echo 'Pesan yang dikirim: '.$text.PHP_EOL;
            print_r($hasil);
        }
    }
}

// pencetakan versi dan info waktu server, berfungsi jika test hook
echo 'Ver. '. phpversion().' OK Start!'.PHP_EOL.date('d-m-Y H:i:s').PHP_EOL;

function printUpdates($result)
{
    foreach ($result as $obj) {
        // echo $obj['message']['text'].PHP_EOL;
    processMessage($obj);
        $last_id = $obj['update_id'];
    }

    return $last_id;
}


// AKTIFKAN INI jika menggunakan metode poll
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/*
$last_id = null;
while (true) {
    $result = getUpdates($last_id);
    if (!empty($result)) {
        echo '+';
        $last_id = printUpdates($result);
    } else {
        echo '-';
    }

    sleep(1);
}
*/
// AKTIFKAN INI jika menggunakan metode webhook
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
  exit;
} else {
  processMessage($update);
}

?>
