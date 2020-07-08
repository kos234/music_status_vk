package progs.kos;

import com.alibaba.fastjson.*;
import com.mysql.cj.jdbc.exceptions.SQLError;
import com.vk.api.sdk.exceptions.ApiParamException;
import com.vk.api.sdk.objects.photos.PhotoAlbumFull;
import com.vk.api.sdk.objects.photos.PhotoUpload;
import com.vk.api.sdk.objects.photos.responses.PhotoUploadResponse;
import com.vk.api.sdk.objects.status.Status;
import com.vk.api.sdk.client.TransportClient;
import com.vk.api.sdk.client.VkApiClient;
import com.vk.api.sdk.client.actors.UserActor;
import com.vk.api.sdk.exceptions.ApiException;
import com.vk.api.sdk.exceptions.ClientException;
import com.vk.api.sdk.httpclient.HttpTransportClient;

import javax.net.ssl.HttpsURLConnection;
import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.ProtocolException;
import java.net.URL;
import java.sql.*;
import java.util.Base64;
import java.util.Properties;

import static java.lang.Runtime.getRuntime;

public class App extends Const{
    public static void main( String[] args ){
        final int TIME_SLEEP = 30 * 1000;
        final String AUTHORISATION_SPOTIFY = Base64.getEncoder().encodeToString("dde6a297cdc345059eda98c69ba722c0:ce45e9cbc7da47019b6540f9abe00a68".getBytes());
        TransportClient transportClient = HttpTransportClient.getInstance();
        VkApiClient vk = new VkApiClient(transportClient);
        try {
            Connection MySQL = connection(url, user_name, user_password);
            ResultSet start = MySQL.prepareStatement("SELECT `isStart` FROM `active_state`").executeQuery();
            if(start.next()) {
                while (start.getInt("isStart") == 1) {
                    try {
                        long timeStart = System.currentTimeMillis();
                        ResultSet infoUsers;
                        try {
                            infoUsers = MySQL.prepareStatement("SELECT * FROM `dataSettings`").executeQuery();
                        } catch (SQLException  e){
                            e.printStackTrace();
                            MySQL = connection(url, user_name, user_password);
                            infoUsers = MySQL.prepareStatement("SELECT * FROM `dataSettings`").executeQuery();
                        }
                        while (infoUsers.next()) {
                            UserActor actor = new UserActor(infoUsers.getInt("user_id"), infoUsers.getString("tokenVK"));
                            switch (infoUsers.getString("operationId")) {
                                case "start":
                                    Status status = vk.status().get(actor).userId(actor.getId()).execute();
                                    try {
                                        MySQL.prepareStatement("UPDATE dataSettings set lastStatus = \'" + status.getText() + "\', operationID = 'on' WHERE user_id = " + actor.getId()).execute();
                                    }catch (SQLException  e){
                                        e.printStackTrace();
                                        MySQL = connection(url, user_name, user_password);
                                        MySQL.prepareStatement("UPDATE dataSettings set lastStatus = \'" + status.getText() + "\', operationID = 'on' WHERE user_id = " + actor.getId()).execute();
                                    }
                                    on(infoUsers.getString("tokenSpotify"), infoUsers.getString("refreshTokenSpotify"), AUTHORISATION_SPOTIFY, MySQL, vk, actor, TIME_SLEEP, infoUsers);
                                    break;

                                case "on":
                                    on(infoUsers.getString("tokenSpotify"), infoUsers.getString("refreshTokenSpotify"), AUTHORISATION_SPOTIFY, MySQL, vk, actor, TIME_SLEEP, infoUsers);
                                    break;

                                case "finish":
                                    vk.status().set(actor).text(infoUsers.getString("lastStatus")).execute();
                                    if (infoUsers.getInt("icPhotoMusic") == 1) {
                                        try {
                                            vk.photos().deleteAlbum(actor, infoUsers.getInt("albumForPhotoMusic")).execute();
                                        } catch (ApiParamException e) {
                                            e.printStackTrace();
                                        }
                                        try {
                                            MySQL.prepareStatement("UPDATE dataSettings SET `albumForPhotoMusic` = 0, operationID = 'off', lastTrack = '' WHERE user_id = " + actor.getId()).execute();
                                        }catch (SQLException  e){
                                            e.printStackTrace();
                                            MySQL = connection(url, user_name, user_password);
                                            MySQL.prepareStatement("UPDATE dataSettings SET `albumForPhotoMusic` = 0, operationID = 'off', lastTrack = '' WHERE user_id = " + actor.getId()).execute();
                                        }
                                    } else
                                        try {
                                            MySQL.prepareStatement("UPDATE dataSettings SET  operationID = 'off', lastTrack = '' WHERE user_id = " + actor.getId()).execute(); //Не говно код, а уменьшение запросов
                                        }catch (SQLException  e){
                                            e.printStackTrace();
                                            MySQL = connection(url, user_name, user_password);
                                            MySQL.prepareStatement("UPDATE dataSettings SET  operationID = 'off', lastTrack = '' WHERE user_id = " + actor.getId()).execute(); //Не говно код, а уменьшение запросов
                                        }

                                    break;
                            }
                        }

                        try {
                            MySQL.prepareStatement("UPDATE active_state SET  active_time = " + (long)System.currentTimeMillis()/1000).execute();
                        }catch (SQLException  e){
                            e.printStackTrace();
                            MySQL = connection(url, user_name, user_password);
                            MySQL.prepareStatement("UPDATE active_state SET  active_time = " + (long)System.currentTimeMillis()/1000).execute();
                        }
                        long sleep = TIME_SLEEP - (System.currentTimeMillis() - timeStart);
                        if(sleep >= 0) {
                            synchronized(MySQL) {
                                try {
                                    MySQL.wait(1000);
                                } catch (InterruptedException e) {
                                    e.printStackTrace();
                                }
                            }
                            Thread.sleep(sleep);
                        }
                        try {
                            start = MySQL.prepareStatement("SELECT `isStart` FROM `active_state`").executeQuery();
                        }catch (SQLException  e){
                            e.printStackTrace();
                            MySQL = connection(url, user_name, user_password);
                            start = MySQL.prepareStatement("SELECT `isStart` FROM `active_state`").executeQuery();
                        }
                        start.next();
                    } catch (IOException e) {
                        e.printStackTrace();
                    } catch (ApiException e) {
                        e.printStackTrace();
                    } catch (ClientException e) {
                        e.printStackTrace();
                    } catch (InterruptedException e) {
                        e.printStackTrace();
                    }
                }
            }
        }catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public static void on(String tokenSpotify, String refreshTokenSpotify, String authorizationSpotify, Connection MySQL, VkApiClient vk, UserActor actor, int TIME_SLEEP, ResultSet infoUser) throws InterruptedException, SQLException, IOException {
        try {
            JSONObject musicFullJson = getRequestJSON(new URL("https://api.spotify.com/v1/me/player/currently-playing?access_token=" + tokenSpotify));
            if(!(boolean)musicFullJson.get("is_playing"))
                throw new NullPointerException(String.valueOf(infoUser.getInt("icText")));

            JSONObject music = (JSONObject) musicFullJson.get("item");
            String artists = "", album = "", track = "", statusTrack = "";

            JSONObject albumJSON = (JSONObject) music.get("album");
            JSONObject albumGet = getRequestJSON(new URL(albumJSON.get("href") + "?access_token=" + tokenSpotify));
            if(albumGet.getInteger("total_tracks") > 1){
                if(albumJSON.get("type").equals("album"))
                    album = ", Альбом: " + albumJSON.get("name");
                else if(albumJSON.get("type").equals("single"))
                    album = ", Синг: " + albumJSON.get("name");
                else if(albumJSON.get("type").equals("compilation"))
                    album = ", Сборник: " + albumJSON.get("name");
            }

            JSONArray arrayArtists = (JSONArray) music.get("artists");
            for (int i = 0; i < arrayArtists.size(); i++){
                JSONObject name = (JSONObject) arrayArtists.get(i);
                if(i == 0)
                    artists = name.get("name").toString();
                else
                    artists = String.join(", ", artists, name.get("name").toString());
            }

            track = artists + " — " + music.get("name") + album;

            if (infoUser.getInt("icText") == 1){
                statusTrack = "Слушает: " + artists + " — " + music.get("name") + album;
                if(statusTrack.length() >= 140 && infoUser.getInt("isLength") == 1){
                    statusTrack = "Слушает: " + artists + " — " +  music.get("name");
                }
            }else{
                statusTrack = track;
                if(statusTrack.length() >= 140 && infoUser.getInt("isLength") == 1){
                    statusTrack = artists + " — " +  music.get("name");
                }
            }
            String cheakSleep = vk.status().get(actor).userId(actor.getId()).execute().getText().split(":")[0];
            boolean icSleep = cheakSleep.equals("На паузе") || cheakSleep.equals("Слушал");

            if(!track.equals(infoUser.getString("lastTrack").replace("@", "'")) || icSleep) {
                vk.status().set(actor).text(statusTrack).execute();
                try {
                    MySQL.prepareStatement("UPDATE dataSettings set lastTrack = \'" + track.toString().replace("'", "@") + "\' WHERE user_id = " + actor.getId()).execute();
                }catch (SQLException  e){
                    e.printStackTrace();
                    MySQL = connection(url, user_name, user_password);
                    MySQL.prepareStatement("UPDATE dataSettings set lastTrack = \'" + track.toString().replace("'", "@") + "\' WHERE user_id = " + actor.getId()).execute();
                }

                if(infoUser.getInt("icPhotoMusic") == 1 && icSleep){
                    JSONArray urls = albumJSON.getJSONArray("images");
                    JSONObject urlPhoto =  urls.getJSONObject(0);
                    if(infoUser.getInt("albumForPhotoMusic") == 0){
                        PhotoAlbumFull photoAlbumFull = vk.photos().createAlbum(actor, "Последние прослушиваемые треки").description("В этом альбоме находятся обложки последних прослушиваемых треков пользователя").execute();
                        try {
                            MySQL.prepareStatement("UPDATE dataSettings set albumForPhotoMusic = " + photoAlbumFull.getId() + " WHERE user_id = " + actor.getId()).execute();
                        }catch (SQLException  e){
                            e.printStackTrace();
                            MySQL = connection(url, user_name, user_password);
                            MySQL.prepareStatement("UPDATE dataSettings set albumForPhotoMusic = " + photoAlbumFull.getId() + " WHERE user_id = " + actor.getId()).execute();
                        }
                        photoStatus(photoAlbumFull.getId(), vk, actor, track, MySQL, urlPhoto.get("url").toString());
                    }else
                        photoStatus(infoUser.getInt("albumForPhotoMusic"), vk, actor, track, MySQL, urlPhoto.get("url").toString()); }
            }

        }catch (IOException e){
            System.out.print("IOException");
            Process process = getRuntime().exec("curl -H \"Authorization: Basic " + authorizationSpotify + "\" -d grant_type=refresh_token -d refresh_token=" + refreshTokenSpotify + " -d redirect_uri=https://music-statuc-by-kos.herokuapp.com/spotify https://accounts.spotify.com/api/token --ssl-no-revoke");
            InputStream stream = process.getInputStream();
            BufferedReader reader = new BufferedReader(new InputStreamReader(stream));
            StringBuilder result = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                result.append(line);
            }
            System.out.print(result.toString());
            JSONObject json = JSON.parseObject(result.toString());

            try {
                MySQL.prepareStatement("UPDATE dataSettings SET `tokenSpotify` = \'" + json.get("access_token").toString() + "\' WHERE user_id = " + actor.getId()).execute();
            }catch (SQLException  i){
                e.printStackTrace();
                MySQL = connection(url, user_name, user_password);
                MySQL.prepareStatement("UPDATE dataSettings SET `tokenSpotify` = \'" + json.get("access_token").toString() + "\' WHERE user_id = " + actor.getId()).execute();
            }
            on(json.get("access_token").toString(), refreshTokenSpotify, authorizationSpotify, MySQL, vk, actor, TIME_SLEEP, infoUser);
        }catch (NullPointerException e){
            //Сон Sleep
            try {
                if(!infoUser.getString("lastTrack").equals("")) {
                    if (e.getMessage().equals("1") && !vk.status().get(actor).userId(actor.getId()).execute().getText().equals("На паузе: " + infoUser.getString("lastTrack"))) {
                        vk.status().set(actor).text("На паузе: " + infoUser.getString("lastTrack")).execute();
                    } else if (infoUser.getInt("icStop") == 1 && !vk.status().get(actor).userId(actor.getId()).execute().getText().equals("Слушал: " + infoUser.getString("lastTrack"))) {
                        vk.status().set(actor).text("Слушал: " + infoUser.getString("lastTrack")).execute();
                    } else {
                        vk.status().set(actor).text(infoUser.getString("lastStatus")).execute();
                        if (infoUser.getInt("icPhotoMusic") == 1) {
                            if (infoUser.getInt("albumForPhotoMusic") != 0) {
                                try {
                                    MySQL.prepareStatement("UPDATE dataSettings SET `albumForPhotoMusic` = 0, `lastTrack` = '' WHERE user_id = " + actor.getId()).execute();
                                } catch (SQLException i) {
                                    e.printStackTrace();
                                    MySQL = connection(url, user_name, user_password);
                                    MySQL.prepareStatement("UPDATE dataSettings SET `albumForPhotoMusic` = 0, `lastTrack` = '' WHERE user_id = " + actor.getId()).execute();
                                }
                                vk.photos().deleteAlbum(actor, infoUser.getInt("albumForPhotoMusic")).execute();
                            }
                        } else
                            try {
                                MySQL.prepareStatement("UPDATE dataSettings SET `lastTrack` = '' WHERE user_id = " + actor.getId()).execute();
                            } catch (SQLException i) {
                                e.printStackTrace();
                                MySQL = connection(url, user_name, user_password);
                                MySQL.prepareStatement("UPDATE dataSettings SET `lastTrack` = '' WHERE user_id = " + actor.getId()).execute();
                            }
                    }
                }

            } catch (ApiException ex) {
                ex.printStackTrace();
            } catch (ClientException ex) {
                ex.printStackTrace();
            }

            //Очищаем альбом
        }catch (Exception e){
            e.printStackTrace();
        }
    }

    public static void photoStatus(int album_id, VkApiClient vk, UserActor actor, String track, Connection MySQL, String photoUrl) throws SQLException, ClientException, ApiException {
        try {


            File photo = File.createTempFile("photo", ".jpg");
            if(!photo.exists()) {
                //Создаем его.
                photo.createNewFile();
            }
            URL connection = new URL(photoUrl);
            HttpURLConnection urlconn;
            urlconn = (HttpURLConnection) connection.openConnection();
            urlconn.setRequestMethod("GET");
            urlconn.connect();
            InputStream in = null;
            in = urlconn.getInputStream();
            OutputStream writer = new FileOutputStream(photo);
            byte buffer[] = new byte[1];
            int c = in.read(buffer);
            while (c > 0) {
                writer.write(buffer, 0, c);
                c = in.read(buffer);
            }
            writer.flush();
            writer.close();
            in.close();

            PhotoUpload photoUpload = vk.photos().getUploadServer(actor).albumId(album_id).execute();

            PhotoUploadResponse photoUploadResponse = vk.upload().photo(photoUpload.getUploadUrl(), photo).execute();
            vk.photos().save(actor).server(photoUploadResponse.getServer()).photosList(photoUploadResponse.getPhotosList()).hash(photoUploadResponse.getHash()).albumId(album_id).caption(track).execute();
            photo.deleteOnExit();
        } catch (ApiException e) {
            e.printStackTrace();
            PhotoAlbumFull photoAlbumFull = vk.photos().createAlbum(actor, "Последние прослушиваемые треки").description("В этом альбоме находятся обложки последних прослушиваемых треков пользователя").execute();
            try {
                MySQL.prepareStatement("UPDATE dataSettings SET `albumForPhotoMusic` = " + photoAlbumFull.getId() + " WHERE user_id = " + actor.getId()).execute();
            }catch (SQLException  i){
                e.printStackTrace();
                MySQL = connection(url, user_name, user_password);
                MySQL.prepareStatement("UPDATE dataSettings SET `albumForPhotoMusic` = " + photoAlbumFull.getId() + " WHERE user_id = " + actor.getId()).execute();
            }
            photoStatus(photoAlbumFull.getId(), vk, actor, track, MySQL, photoUrl);
        } catch (ClientException e) {
            e.printStackTrace();
        } catch (FileNotFoundException e) {
            e.printStackTrace();
        } catch (ProtocolException e) {
            e.printStackTrace();
        } catch (MalformedURLException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    public static JSONObject getRequestJSON(URL url) throws IOException {
        HttpsURLConnection connection = (HttpsURLConnection) url.openConnection();
        connection.setDoOutput(true);
        connection.setDoInput(true);
        connection.setRequestMethod("GET");
        connection.setRequestProperty("Accept-Charset", "application/json; charset=UTF-8");
        connection.setConnectTimeout(15000);
        connection.connect();

        InputStream in = new BufferedInputStream(connection.getInputStream());
        BufferedReader reader = new BufferedReader(new InputStreamReader(in));
        StringBuilder result = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            result.append(line);
        }

        JSONObject json = JSON.parseObject(result.toString());
        return json;
    }

    public static Connection connection (String url, String user, String password) throws SQLException {
        Connection MySQL = DriverManager.getConnection(url, user, password);
        MySQL.prepareStatement("SET NAMES 'utf8'").execute();
        MySQL.prepareStatement("SET CHARACTER SET utf8mb4;").execute();
        MySQL.prepareStatement("CREATE TABLE IF NOT EXISTS `active_state`(`active_time` BigInt( 255 ) NOT NULL, `isStart` TinyInt( 1 ) NOT NULL DEFAULT 1 ) ENGINE = InnoDB;").execute();
        MySQL.prepareStatement("CREATE TABLE IF NOT EXISTS `dataSettings` (`operationId` VarChar( 255 ) NOT NULL DEFAULT 'off',`isLength` TinyInt( 1 ) NOT NULL DEFAULT 1 ,`icText` TinyInt( 1 ) NOT NULL DEFAULT 1 ,`lastStatus` VarChar( 255 ) NULL,`refreshTokenSpotify` VarChar( 400 ) NOT NULL, `tokenSpotify` VarChar( 400 ) NOT NULL,`lastTrack` VarChar( 400 ) NULL, `icPhotoMusic` TinyInt( 1 ) NOT NULL DEFAULT 0, `albumForPhotoMusic` Int( 255 ) NOT NULL DEFAULT 0, `user_id` INT ( 255 ) NOT NULL, CONSTRAINT `unique_user_id` UNIQUE( `user_id` ), `tokenVK` VarChar( 255 ) NOT NULL) ENGINE = InnoDB;").execute();
        return MySQL;
    }
}
