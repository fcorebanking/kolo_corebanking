<!-- The core Firebase JS SDK is always required and must be listed first -->
<script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js"></script>

<!-- TODO: Add SDKs for Firebase products that you want to use
    https://firebase.google.com/docs/web/setup#available-libraries -->


<script>
    // Your web app's Firebase configuration
    var firebaseConfig = {
        apiKey: "AIzaSyBMzWTMBYGdIU1-PGvanq30yXNWvQfwmSY",
        authDomain: "wealthtribe-e2ceb.firebaseapp.com",
        projectId: "wealthtribe-e2ceb",
        storageBucket: "wealthtribe-e2ceb.appspot.com",
        messagingSenderId: "874329931781",
        appId: "1:874329931781:web:e8fc6ae7063a6eccd29c6c",
        measurementId: "G-W1B7YSWZN6"
    };
    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);

    const messaging = firebase.messaging();

    function initFirebaseMessagingRegistration() {
        messaging.requestPermission().then(function () {
            return messaging.getToken()
        }).then(function(token) {

            axios.post("{{ route('fcmToken') }}",{
                _method:"PATCH",
                token
            }).then(({data})=>{
                console.log(data)
            }).catch(({response:{data}})=>{
                console.error(data)
            })

        }).catch(function (err) {
            console.log(`Token Error :: ${err}`);
        });
    }

    initFirebaseMessagingRegistration();

    messaging.onMessage(function({data:{body,title}}){
        new Notification(title, {body});
    });
</script>
