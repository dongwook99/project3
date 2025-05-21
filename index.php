<?php
// 환경 변수에서 DB 및 Redis 연결 정보 가져오기
$host = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

$redisHost = getenv('REDIS_HOST');
$redisPort = getenv('REDIS_PORT');

// 환경 변수 값 확인
if (!$redisHost || !$redisPort) {
    die("Error: Redis host or port not set properly.<br>");
}

$redis = new Redis();

try {
    // DB 연결
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connection to DB successful!<br>";

    // Redis 연결
    $redis->connect($redisHost, $redisPort);
    if ($redis->ping() == "+PONG") {
        echo "✅ Successfully connected to Redis at $redisHost:$redisPort!<br>";
    } else {
        echo "❌ Failed to connect to Redis.<br>";
    }

    // SQL 쿼리 실행: reservations 테이블 조회
    $sql = "SELECT * FROM reservations";
    $stmt = $pdo->query($sql);

    // Redis 캐시 확인
    $redisKey = "reservation_list";
    $cachedReservations = $redis->get($redisKey);

    if ($cachedReservations) {
        echo "✅ Using cached data from Redis.<br>";
        $reservations = json_decode($cachedReservations, true);
    } else {
        echo "⚠️ No cached data found, fetching from DB.<br>";
        $reservations = [];
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reservations[] = $row;
            }
            // 1시간(3600초) 동안 Redis에 저장
            $redis->setex($redisKey, 3600, json_encode($reservations));
        }
    }

    // 예약 데이터 출력
    echo "<h3>📝 Hospital Reservation List:</h3><table border='1'><tr><th>ID</th><th>Name</th><th>Specialty</th></tr>";
    foreach ($reservations as $reservation) {
        echo "<tr><td>" . htmlspecialchars($reservation['id']) . "</td><td>" . htmlspecialchars($reservation['name']) . "</td><td>" . htmlspecialchars($reservation['specialty']) . "</td></tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "❌ DB 연결 실패: " . $e->getMessage() . "<br>";
} catch (RedisException $e) {
    echo "❌ Redis 연결 실패: " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "❌ 오류 발생: " . $e->getMessage() . "<br>";
}
?>
