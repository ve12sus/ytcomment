<?php
//this is the dev branch
class Server {

    public function serve() {

		$uri = $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];
		$paths = explode('/', $this->paths($uri));
		$collection = $paths[3];
		$vid_id = $paths[4];
		$subresource = $paths[5];
		$resourceid = $paths[6];

		if ($collection == 'videos') {

			if (empty($vid_id)) {
				$this->handle_videos($method);
			} else {
				if (empty($subresource)) {
					$this->handle_id($method, $vid_id);
				} else {
					$this->handle_sub($method, $vid_id, $subresource, $resourceid);
				}
			}
		} else {
			//currently only handle 'videos' resource
			header('HTTP/1.1 404 Not Found');
		}
	}

	private function handle_videos($method) {
		switch($method) {
		case 'GET':
			$this->retrieve_videos();
			break;
		case 'POST':
			$this->create_video();
			break;
		default:
			header('HTTP/1.1 405 Method Not Allowed');
			header('Allow: GET, POST');
			break;
		}
	}

	private function handle_id($method, $vid_id) {
		switch($method) {
		case 'DELETE':
			$this->delete_video($vid_id);
			break;

		case 'GET':
			$this->get_video($vid_id);
			break;

		default:
			header('HTTP/1.1 405 Method Not Allowed');
			header('Allow: GET, DELETE');
			break;
		}
	}

	private function handle_sub($method, $vid_id, $subresource, $resourceid) {
		switch($method) {
		case 'GET':
			$this->get_sub($vid_id, $subresource);
			break;

		case 'POST':
			$this->update_sub($vid_id, $subresource, $resourceid);
			break;

		case 'DElETE':
			$this->delete_comment($vid_id, $subcollection, $subresource);
			break;

		default:
			header('HTTP/1.1 405 Method Not Allowed');
			header('Allow: GET, POST');
			break;
		}
	}

	private function update_sub($vid_id, $subresource, $resourceid) {
		$inputJSON = file_get_contents('php://input');
		$input = json_decode($inputJSON, TRUE);
		$resource = $input[$subresource];
		$time = $input['time'];
		$comment = $input['comments'];
		$style = $input['style'];
		$sql ="";
		if ($subresource == 'title' or $subresource == 'youtubeId') {
			$sql = "UPDATE videos SET $subresource = '$resource' WHERE id=$vid_id";
		} elseif ($subresource == 'comments') {
			if ($resourceid) {
				$sql = "UPDATE comments SET $subresource = '$resource' WHERE time = $resourceid and id=$vid_id";
			} else {
				$sql = "INSERT INTO comments (id, time, comments, style)
						values ($vid_id, $time, '$comment', '$style')";
			}
		} else {
			header('HTTP/1.1 404 Not Found');
		}
		$new = $this->mySQLconnect();
		$new->query($sql);
		if ($new->affected_rows < 1) {
			header('HTTP/1.1 400 Bad Request');
			die('Wrong Values');
		} else {
			header('HTTP/1.1 200 OK');
			echo 'Resource updated';
		}
	}

	private function get_sub($vid_id, $subresource) {
	}

	private function delete_comment() {
	}

	private function create_video()	{
		$inputJSON = file_get_contents('php://input');
		$input = json_decode($inputJSON, TRUE);
		$title = $input['title'];
		$youtubeId = $input['youtubeId'];
		$sql = "INSERT INTO videos (title, youtubeId)
			    VALUES ('$title', '$youtubeId')";
		$new = $this->mySQLconnect();
		$new->query($sql);
		if ($new->affected_rows < 1) {
			header('HTTP/1.1 400 Bad Request');
			die('Wrong Values');
		} else {
			header('HTTP/1.1 201 Created');
			echo 'New video created';
		}
	}

	private function retrieve_videos() {
		$sql = "SELECT * FROM videos";
		$videos = array();
		$new = $this->mySQLconnect();
		$result = $new->query($sql);
		while ($row = $result->fetch_assoc()) {
			$videos[] = $row;
		}
		if ($videos == []) {

			header('HTTP/1.1 404 Not Found');
			echo "Record not found";
		} else {
			header('Content-type: application/json');
			echo json_encode($videos, JSON_PRETTY_PRINT);
		}
	}

	private function delete_video($vid_id) {
		$sql = "DELETE FROM videos WHERE id=$vid_id";
		$new = $this->mySQLconnect();
		$new->query($sql);
		if ($new->affected_rows < 1) {
			header('HTTP/1.1 404 Not Found');
			die('Invalid id');
		} else {
			header('HTTP/1.1 200 OK');
		}
	}

	private function get_video($vid_id) {
		$sql = "SELECT * FROM videos WHERE id = $vid_id";
		$new = $this->mySQLconnect();
		$result = $new->query($sql);
		$row = $result->fetch_assoc();
		if ($row == NULL) {
			header('HTTP/1.1 404 Not Found');
			echo "Video not found";
		} else {
			header('Content-type: application/json');
			echo json_encode($row, JSON_PRETTY_PRINT);
		}
    }

	private function paths($url) {
		$uri = parse_url($url);
		return $uri['path'];
	}

	private function mySQLconnect() {
		$servername = "localhost";
		$username = "root";
		$password = "admin";
		$dbname = "ytc";

		$connection = new mysqli($servername, $username, $password, $dbname);
		if ($connection->connect_errno) {
			die("Unable to connect to database" . $connection->connect_error);
		}
		return $connection;
	}
}

$server = new Server;
$server->serve();

?>
