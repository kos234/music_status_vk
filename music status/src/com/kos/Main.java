package com.kos;

import com.alibaba.fastjson.JSON;
import com.alibaba.fastjson.JSONArray;
import com.alibaba.fastjson.JSONObject;
import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.client.params.ClientPNames;
import org.apache.http.client.params.CookiePolicy;
import org.apache.http.entity.ContentType;
import org.apache.http.entity.mime.MultipartEntity;
import org.apache.http.entity.mime.MultipartEntityBuilder;
import org.apache.http.entity.mime.content.FileBody;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.impl.client.HttpClients;

import javax.net.ssl.HttpsURLConnection;
import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.ProtocolException;
import java.net.URL;
import java.sql.*;
import java.util.Date;

import static java.lang.Runtime.getRuntime;


public class Main {

    public static void main(String[] args) {
            String tokenVk = "",
                tokenSpotify = "",
                refreshTokenSpotify = "",
                versionAPIVk = "5.103",
                authorizationSpotify = ""; //base 64 encode

            final int TIME_SLEEP = 60000; //default 60000 = 1 min

        try {
            String[] help = getConf();

            Connection MySQL = connection(help[0], help[1], help[2]);
            System.out.print("Подключились!");
            authorizationSpotify = help[3];
            PreparedStatement query = MySQL.prepareStatement("CREATE TABLE IF NOT EXISTS `dataSettings` (`operationId` VarChar( 255 ) NOT NULL DEFAULT 'off',`lastStatus` VarChar( 255 ) NULL,`refreshTokenSpotify` VarChar( 400 ) NOT NULL, `tokenSpotify` VarChar( 400 ) NOT NULL,`lastMusicStatus` VarChar( 400 ) NULL, `isPhotoMusic` TinyInt( 1 ) NOT NULL DEFAULT 0, `albumForPhotoMusic` Int( 255 ) NULL, `user_id` VarChar( 255 ) NOT NULL, CONSTRAINT `unique_user_id` UNIQUE( `user_id` ), `tokenVK` VarChar( 255 ) NOT NULL) ENGINE = InnoDB;");
            query.executeUpdate();
            query = MySQL.prepareStatement("CREATE TABLE IF NOT EXISTS `active`(`active_time` BigInt( 255 ) NOT NULL ) ENGINE = InnoDB;");
            query.executeUpdate();

            while (true){
                MySQL = connection(help[0], help[1], help[2]);
                query = MySQL.prepareStatement("SELECT * FROM `dataSettings`");
                ResultSet infoUsers = query.executeQuery();
                while (infoUsers.next()) {
                    tokenSpotify = infoUsers.getString("tokenSpotify");
                    tokenVk = infoUsers.getString("tokenVK");
                    refreshTokenSpotify = infoUsers.getString("refreshTokenSpotify");
                    switch (infoUsers.getString("operationId")) {
                        case "start":
                            JSONObject text = (JSONObject) getRequestJSON(new URL("https://api.vk.com/method/status.get?access_token=" + tokenVk  + "&user_id=" + infoUsers.getString("user_id") + "&v=" + versionAPIVk)).get("response");
                            query = MySQL.prepareStatement("UPDATE dataSettings set lastStatus = \'" + text.get("text").toString() +"\', operationID = 'on'");
                            query.executeUpdate();
                            on(tokenSpotify, refreshTokenSpotify, authorizationSpotify, MySQL, tokenVk, versionAPIVk, TIME_SLEEP, infoUsers);
                            break;

                        case "on":
                            on(tokenSpotify, refreshTokenSpotify, authorizationSpotify, MySQL, tokenVk, versionAPIVk, TIME_SLEEP, infoUsers);
                            break;

                        case "finish" :
                                getRequestJSON(new URL("https://api.vk.com/method/status.set?access_token=" + tokenVk  + "&text=" + replaceSpace(infoUsers.getString("lastStatus")) + "&v=" + versionAPIVk));
                            break;
                    }
                }

                query = MySQL.prepareStatement("UPDATE `active` SET `active_time` = '" + new Date().getTime() + "'");
                            query.executeUpdate();

                Thread.sleep(TIME_SLEEP);
            }

        } catch (SQLException | MalformedURLException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        } catch (InterruptedException e) {
            e.printStackTrace();
        }

    }

    public static String[] getConf() throws IOException {
            BufferedReader reader = null;
            reader = new BufferedReader(new FileReader(new File("conf.txt")));
            StringBuilder result = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                result.append(line);
            }

        return result.toString().split("~");
    }

    public static Connection connection (String url, String user, String password) throws SQLException {
        Connection MySQL = DriverManager.getConnection(url, user, password);

        PreparedStatement query = MySQL.prepareStatement("SET NAMES 'utf8'");
        query.executeUpdate();
        return MySQL;
    }

    public static void on(String tokenSpotify, String refreshTokenSpotify, String authorizationSpotify, Connection MySQL, String tokenVk, String versionAPIVk, int TIME_SLEEP, ResultSet infoUser) throws InterruptedException, SQLException, IOException {
        try {
            JSONObject musicFullJson = getRequestJSON(new URL("https://api.spotify.com/v1/me/player/currently-playing?access_token=" + tokenSpotify));
            if(musicFullJson.get("is_playing").equals(false))
                throw new NullPointerException("sleep");
            JSONObject music = (JSONObject) musicFullJson.get("item");
            String artists = "", album = "";

            JSONArray arrayArtists = (JSONArray) music.get("artists");
            for (int i = 0; i < arrayArtists.size(); i++){
                JSONObject name = (JSONObject) arrayArtists.get(i);
                if(i == 0)
                    artists = name.get("name").toString();
                else
                    artists = String.join(",%20", artists, name.get("name").toString());
            }

            JSONObject albumJSON = (JSONObject) music.get("album");
            if(albumJSON.get("type").equals("album")){
                album = ",%20Альбом:%20" + replaceSpace(albumJSON.get("name").toString());
            }

            String url = "https://api.vk.com/method/status.set?access_token=" + tokenVk  + "&text=Слушает:%20" + replaceSpace(artists) + "%20-%20" + replaceSpace(music.get("name").toString()) + album + "&v=" + versionAPIVk;
            getRequestJSON(new URL(url));

           /* PreparedStatement query = MySQL.prepareStatement("SELECT `isPhotoMusic`, `lastMusicStatus`, `albumForPhotoMusic` FROM `dataSettings`");
            ResultSet res = query.executeQuery();
            if(res.next())
                switch (res.getInt("isPhotoMusic")){
                case 1:
                    JSONObject albumVK = (JSONObject) getRequestJSON(new URL("https://api.vk.com/method/photos.createAlbum?access_token=" + tokenVk + "&title=" + replaceSpace("Последние прослушиваемые треки") + "&description=" + replaceSpace("В этом альбоме находятся обложки последних прослушиваемых треков пользователя") + "&v=" + versionAPIVk)).get("response");
                    query = MySQL.prepareStatement("UPDATE dataSettings set albumForPhotoMusic = '"+ albumVK.get("id").toString() +"', isPhotoMusic = 2");
                    query.executeUpdate();
                    upLoadPhoto(albumVK.get("id").toString(), tokenVk, versionAPIVk);
                    break;

                    case 2:
                        upLoadPhoto(Integer.toString(res.getInt("albumForPhotoMusic")), tokenVk, versionAPIVk);
                        break;
            } */ //Обновление)0)
        }catch (IOException e){
            e.printStackTrace();
            try {
                Process process = getRuntime().exec("curl -H \"Authorization: Basic " + authorizationSpotify + "\" -d grant_type=refresh_token -d refresh_token=" + refreshTokenSpotify + " -d redirect_uri=https://vk.com/i_love_python https://accounts.spotify.com/api/token --ssl-no-revoke");
                InputStream stream = process.getInputStream();
                BufferedReader reader = new BufferedReader(new InputStreamReader(stream));
                StringBuilder result = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    result.append(line);
                }
                System.out.print(result.toString());
                JSONObject json = JSON.parseObject(result.toString());

                PreparedStatement query = MySQL.prepareStatement("UPDATE dataSettings set tokenSpotify = \'" + json.get("access_token").toString() + "\'");
                query.executeUpdate();
                on(json.get("access_token").toString(), refreshTokenSpotify, authorizationSpotify, MySQL, tokenVk, versionAPIVk, TIME_SLEEP, infoUser);
            } catch (Throwable cause) {
            }
        }catch (NullPointerException e){
            //Сон Sleep
                getRequestJSON(new URL("https://api.vk.com/method/status.set?access_token=" + tokenVk  + "&text=" + replaceSpace(infoUser.getString("lastStatus")) + "&v=" + versionAPIVk));
                //Очищаем альбом
        }
    }

    public static void upLoadPhoto(String idAlbum, String tokenVk, String versionAPIVk) throws IOException {
        JSONObject server = (JSONObject) getRequestJSON(new URL("https://api.vk.com/method/photos.getUploadServer?access_token=" + tokenVk + "&album_id=" + idAlbum + "&v=" + versionAPIVk)).get("response");
        CloseableHttpClient httpClient = HttpClients.createDefault();
        HttpPost uploadFile = new HttpPost(server.getString("upload_url"));
        MultipartEntityBuilder builder = MultipartEntityBuilder.create();
        uploadFile.addHeader("Content-Type", "multipart/form-data");
        uploadFile.addHeader("charset", "UTF-8");
// This attaches the file to the POST:
        File f = new File("test.png");
        builder.addBinaryBody(
                "file1",
                new FileInputStream(f),
                ContentType.MULTIPART_FORM_DATA,
                f.getName()
        );

        HttpEntity multipart = builder.build();
        uploadFile.setEntity(multipart);
        CloseableHttpResponse response = httpClient.execute(uploadFile);
        HttpEntity responseEntity = response.getEntity();
        System.out.println(responseEntity);
    }

    public static String replaceSpace(String str){
        try {
            String[] arr = str.split(" ");
            String string = "";

            for (int i = 0; i < arr.length; i++) {
                if (i == 0)
                    string = arr[i];
                else
                    string = String.join("%20", string, arr[i]);
            }

            String[] arrAnd = string.split("&");
            String stringReturn = "";
            for (int i = 0; i < arrAnd.length; i++) {
                if (i == 0)
                    stringReturn = arrAnd[i];
                else
                    stringReturn = String.join("and", stringReturn, arrAnd[i]);
            }

            return stringReturn;
        }catch (NullPointerException e){
            return "";
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

}
