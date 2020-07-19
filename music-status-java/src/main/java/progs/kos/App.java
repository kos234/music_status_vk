package progs.kos;

import com.alibaba.fastjson.*;
import com.mysql.cj.jdbc.exceptions.SQLError;
import com.vk.api.sdk.exceptions.ApiParamException;
import com.vk.api.sdk.objects.base.responses.OkResponse;
import com.vk.api.sdk.objects.photos.Photo;
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
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.Base64;
import java.util.List;
import java.util.Properties;

import static java.lang.Runtime.getRuntime;

public class App extends Const{
    public static void main( String[] args ){
        final int TIME_SLEEP = 30 * 1000;
        final String AUTHORISATION_SPOTIFY = Base64.getEncoder().encodeToString("dde6a297cdc345059eda98c69ba722c0:ce45e9cbc7da47019b6540f9abe00a68".getBytes());
        TransportClient transportClient = HttpTransportClient.getInstance();
        VkApiClient vk = new VkApiClient(transportClient);
        try {
            MySQL = connection(url, user_name, user_password);
            ResultSet start = mysqlQuery("SELECT `isStart` FROM `active_state`");
            if(start.next()) {
                while (start.getInt("isStart") == 1) {
                    try {
                        long timeStart = System.currentTimeMillis();
                        ResultSet infoUsers;
                        infoUsers =  mysqlQuery("SELECT * FROM `dataSettings`");

                        while (infoUsers.next()) {
                            UserActor actor = new UserActor(infoUsers.getInt("user_id"), infoUsers.getString("tokenVK"));
                            switch (infoUsers.getString("operationId")) {
                                case "start":
                                    Status status = vk.status().get(actor).userId(actor.getId()).execute();
                                    mysqlQuery("UPDATE dataSettings set lastStatus = \'" + status.getText() + "\', operationID = 'on' WHERE user_id = " + actor.getId());
                                    on(infoUsers.getString("tokenSpotify"), infoUsers.getString("refreshTokenSpotify"), AUTHORISATION_SPOTIFY, vk, actor, infoUsers);
                                    break;

                                case "on":
                                    on(infoUsers.getString("tokenSpotify"), infoUsers.getString("refreshTokenSpotify"), AUTHORISATION_SPOTIFY, vk, actor, infoUsers);
                                    break;

                                case "finish":
                                    vk.status().set(actor).text(infoUsers.getString("lastStatus")).execute();
                                    if (infoUsers.getInt("icPhotoMusic") == 1) {
                                        try {
                                            vk.photos().deleteAlbum(actor, infoUsers.getInt("albumForPhotoMusic")).execute();
                                            mysqlQuery("UPDATE dataSettings SET `albumForPhotoMusic` = 0, operationID = 'off', lastTrack = '' WHERE user_id = " + actor.getId());
                                        }catch (ApiParamException e) {
                                            e.printStackTrace();
                                        }
                                    } else
                                        mysqlQuery("UPDATE dataSettings SET  operationID = 'off', lastTrack = '' WHERE user_id = " + actor.getId()); //Не говно код, а уменьшение запросов


                                    break;
                            }
                        }


                        mysqlQuery("UPDATE active_state SET  active_time = " + (long)System.currentTimeMillis()/1000);

                        long sleep = TIME_SLEEP - (System.currentTimeMillis() - timeStart);
                        if(sleep >= 0)
                            Thread.sleep(sleep);

                        start = mysqlQuery("SELECT `isStart` FROM `active_state`");
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

    public static ResultSet mysqlQuery(String query) {
        ResultSet resultSet = null;
            try {
                resultSet = MySQL.prepareStatement(query).executeQuery();
            } catch (SQLException e) {
                try {
                    MySQL.prepareStatement(query).executeUpdate();
                }catch (SQLException i){
                    MySQL = connection(url, user_name, user_password);
                    mysqlQuery(query);
                }
            }
        return resultSet;
    }

    public static void on(String tokenSpotify, String refreshTokenSpotify, String authorizationSpotify, VkApiClient vk, UserActor actor, ResultSet infoUser) throws SQLException, IOException {
        boolean icSleep = false;
        try {
            icSleep = infoUser.getString("lastTrack").split("%%%").length == 2;
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
                if(statusTrack.length() >= 140 && infoUser.getInt("icLength") == 1){
                    statusTrack = "Слушает: " + artists + " — " +  music.get("name");
                }
            }else{
                statusTrack = track;
                if(statusTrack.length() >= 140 && infoUser.getInt("icLength") == 1){
                    statusTrack = artists + " — " +  music.get("name");
                }
            }

            boolean icLastTrack = track.equals(infoUser.getString("lastTrack").split("%%%")[0].replace("@", "'"));

            if(!icLastTrack || icSleep) {
                vk.status().set(actor).text(statusTrack).execute();
                mysqlQuery("UPDATE dataSettings set lastTrack = \'" + track.toString().replace("'", "@") + "\' WHERE user_id = " + actor.getId());


                if(infoUser.getInt("icPhotoMusic") == 1 && (!icSleep || !icLastTrack)){
                    JSONArray urls = albumJSON.getJSONArray("images");
                    JSONObject urlPhoto =  urls.getJSONObject(0);
                    if(infoUser.getInt("albumForPhotoMusic") == 0){
                        PhotoAlbumFull photoAlbumFull = vk.photos().createAlbum(actor, "Недавно прослушанные треки").description("В этом альбоме находятся обложки недавно прослушанных треков").execute();
                        mysqlQuery("UPDATE dataSettings set albumForPhotoMusic = " + photoAlbumFull.getId() + " WHERE user_id = " + actor.getId());

                        photoStatus(photoAlbumFull.getId(), vk, actor, track, urlPhoto.get("url").toString());
                    }else
                        photoStatus(infoUser.getInt("albumForPhotoMusic"), vk, actor, track, urlPhoto.get("url").toString()); }
            }

        }catch (IOException e){
            Process process = getRuntime().exec("curl -H \"Authorization: Basic " + authorizationSpotify + "\" -d grant_type=refresh_token -d refresh_token=" + refreshTokenSpotify + " -d redirect_uri=https://music-statuc-by-kos.herokuapp.com/spotify https://accounts.spotify.com/api/token --ssl-no-revoke");
            InputStream stream = process.getInputStream();
            BufferedReader reader = new BufferedReader(new InputStreamReader(stream));
            StringBuilder result = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                result.append(line);
            }
            JSONObject json = JSON.parseObject(result.toString());

            mysqlQuery("UPDATE dataSettings SET `tokenSpotify` = \'" + json.get("access_token").toString() + "\' WHERE user_id = " + actor.getId());

            on(json.get("access_token").toString(), refreshTokenSpotify, authorizationSpotify, vk, actor, infoUser);
        }catch (NullPointerException e){
            //Сон Sleep
            try {
                if(!infoUser.getString("lastTrack").equals("")) {
                    String message = "";
                    if(e.getMessage() != null)
                        message = e.getMessage();
                    if (message.equals("") && infoUser.getInt("icStop") == 1) {
                        if(!icSleep) {
                            if (infoUser.getInt("icText") == 1)
                                vk.status().set(actor).text("Слушал: " + infoUser.getString("lastTrack")).execute();
                            else vk.status().set(actor).text(infoUser.getString("lastTrack").replace("@", "'")).execute();
                            mysqlQuery("UPDATE dataSettings set lastTrack = \'" + infoUser.getString("lastTrack") + "%%%sleep\' WHERE user_id = " + actor.getId());
                        }
                    }else if (message.equals("1")){
                        if(!icSleep) {
                            if (infoUser.getInt("icText") == 1)
                                vk.status().set(actor).text("На паузе: " + infoUser.getString("lastTrack")).execute();
                            else vk.status().set(actor).text(infoUser.getString("lastTrack").replace("@", "'")).execute();
                            mysqlQuery("UPDATE dataSettings set lastTrack = \'" + infoUser.getString("lastTrack") + "%%%sleep\' WHERE user_id = " + actor.getId());
                        }
                    } else {
                        vk.status().set(actor).text(infoUser.getString("lastStatus")).execute();
                        if (infoUser.getInt("icPhotoMusic") == 1) {
                            if (infoUser.getInt("albumForPhotoMusic") != 0) {
                                OkResponse okResponse = vk.photos().deleteAlbum(actor, infoUser.getInt("albumForPhotoMusic")).execute();
                                mysqlQuery("UPDATE dataSettings SET `albumForPhotoMusic` = 0, `lastTrack` = '' WHERE user_id = " + actor.getId());
                                System.out.println(okResponse);
                            }
                        } else
                            mysqlQuery("UPDATE dataSettings SET `lastTrack` = '' WHERE user_id = " + actor.getId());

                    }
                }else{
                    String status = vk.status().get(actor).userId(actor.getId()).execute().getText();
                    if(!status.equals(infoUser.getString("lastStatus")))
                        mysqlQuery("UPDATE dataSettings SET `lastStatus` = '"+ status +"' WHERE user_id = " + actor.getId());

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

    public static void photoStatus(int album_id, VkApiClient vk, UserActor actor, String track, String photoUrl) throws ClientException, ApiException {
        File photo = null;
        try {
            photo = File.createTempFile("photo", ".jpg");
            if(!photo.exists()) {
                //Создаем его.
                photo.createNewFile();
            }
            URL connection = new URL(photoUrl);
            HttpURLConnection urlconn = (HttpURLConnection) connection.openConnection();
            urlconn.setRequestMethod("GET");
            urlconn.connect();
            InputStream in = urlconn.getInputStream();
            OutputStream writer = new FileOutputStream(photo);
            byte buffer[] = new byte[1];
            int c = in.read(buffer);
            while (c > 0) {
                writer.write(buffer, 0, c);
                c = in.read(buffer);
            }
            urlconn.disconnect();
            writer.flush();
            writer.close();
            in.close();

            PhotoUploadResponse photoUploadResponse = vk.upload().photo(vk.photos().getUploadServer(actor).albumId(album_id).execute().getUploadUrl(), photo).execute();
            vk.photos().save(actor).server(photoUploadResponse.getServer()).photosList(photoUploadResponse.getPhotosList()).hash(photoUploadResponse.getHash()).albumId(album_id).caption(track).execute();
        } catch (ApiException e) {
            e.printStackTrace();
            PhotoAlbumFull photoAlbumFull = vk.photos().createAlbum(actor, "Недавно прослушанные треки").description("В этом альбоме находятся обложки недавно прослушанных треков").execute();
            mysqlQuery("UPDATE dataSettings SET `albumForPhotoMusic` = " + photoAlbumFull.getId() + " WHERE user_id = " + actor.getId());
            photoStatus(photoAlbumFull.getId(), vk, actor, track, photoUrl);
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
        if(photo != null) {
            photo.deleteOnExit();
                if (!photo.delete())
                    System.out.println("файл " + photo.getAbsolutePath() + " не был удален!");
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

        BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream(), StandardCharsets.UTF_8));
        StringBuilder result = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            result.append(line);
        }

        return JSON.parseObject(result.toString());
    }

    public static Connection connection (String url, String user, String password) {
        Connection MySQL = null;
        try {
            MySQL = DriverManager.getConnection(url, user, password);
            MySQL.prepareStatement("SET NAMES 'utf8'").executeUpdate();
            MySQL.prepareStatement("SET CHARACTER SET utf8mb4;").executeUpdate();
            MySQL.prepareStatement("CREATE TABLE IF NOT EXISTS `active_state`(`active_time` BigInt( 255 ) NOT NULL, `isStart` TinyInt( 1 ) NOT NULL DEFAULT 1 ) ENGINE = InnoDB;").executeUpdate();
            MySQL.prepareStatement("CREATE TABLE IF NOT EXISTS `dataSettings` (`operationId` VarChar( 255 ) NOT NULL DEFAULT 'off',`icLength` TinyInt( 1 ) NOT NULL DEFAULT 0, `icPause` TinyInt( 1 ) NOT NULL DEFAULT 1, `icStop` TinyInt( 1 ) NOT NULL DEFAULT 0 ,`icText` TinyInt( 1 ) NOT NULL DEFAULT 1 ,`lastStatus` VarChar( 255 ) NULL,`refreshTokenSpotify` VarChar( 400 ) NOT NULL, `tokenSpotify` VarChar( 400 ) NOT NULL,`lastTrack` VarChar( 400 ) NULL, `icPhotoMusic` TinyInt( 1 ) NOT NULL DEFAULT 0, `albumForPhotoMusic` Int( 255 ) NOT NULL DEFAULT 0, `user_id` INT ( 255 ) NOT NULL, CONSTRAINT `unique_user_id` UNIQUE( `user_id` ), `tokenVK` VarChar( 255 ) NOT NULL) ENGINE = InnoDB;").executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
            connection(url, user, password);
        }
        return MySQL;
    }
}
