<?php
    function _pluginMain($prmQuery) {
			parse_str($prmQuery, $queryData);
			$items = array();
			if(isset($queryData['episode']) && $queryData['episode']!="")
				watch($items, $queryData['episode'], "s");
			else if(isset($queryData['season']) && $queryData['season']!="")
				showSerieSeasonEpisodes($items, $queryData['season']);
			else if(isset($queryData['serie']) && $queryData['serie']!="")
				showSerieSeasons($items, $queryData['serie']);
			else if(isset($queryData['type']) && $queryData['type']=='series')
				showSeriesMenu($items);
			else if(isset($queryData['type']) && $queryData['type']=='peliculas')
				showMoviesMenu($items, 1);
			else if(isset($queryData['page']) && $queryData['page']!="")
				showMoviesMenu($items, $queryData['page']);
			else if(isset($queryData['movie']) && $queryData['movie']!="")
				watch($items, $queryData['movie'], "");
			else {
				// show type menu
				$items[] = array(
					'id'              => 'umsp://plugins/cuevana?type=series',
					'parentID'        => 'umsp://plugins/cuevana',
					'dc:title'        => 'Series',
					'upnp:class'      => 'object.container',
					'upnp:album_art'  => ''
				);
				$items[] = array(
					'id'              => 'umsp://plugins/cuevana?type=peliculas',
					'parentID'        => 'umsp://plugins/cuevana',
					'dc:title'        => 'Películas',
					'upnp:class'      => 'object.container',
					'upnp:album_art'  => ''
				);
			}
			return $items;
    }

    function showSeriesMenu(&$items) {
	preg_match_all("/<li .*listSeries.*\"([0-9]+)\".*>(.*)<\//",file_get_contents("http://www.cuevana.tv/series"), $series, PREG_SET_ORDER);
	foreach($series as $serie) {
		$items[] = array(
                                        'id'              => "umsp://plugins/cuevana?serie=$serie[1]",
                                        'parentID'        => 'umsp://plugins/cuevana?type=series',
                                        'dc:title'        => $serie[2],
                                        'upnp:class'      => 'object.container',
                                        'upnp:album_art'  => "http://sc.cuevana.tv/box/$serie[1].jpg"
                                );

	}
    }

    function showSerieSeasons(&$items, $serie) {
	preg_match_all("/<li .*listSeries.*,\"([0-9]+)\".*>(.*)<\//U",file_get_contents("http://www.cuevana.tv/list_search_id.php?serie=$serie"), $seasons, PREG_SET_ORDER);
	foreach($seasons as $season) {
		$items[] = array(
                                        'id'              => "umsp://plugins/cuevana?season=$season[1]",
                                        'parentID'        => "umsp://plugins/cuevana?serie=$serie",
                                        'dc:title'        => $season[2],
                                        'upnp:class'      => 'object.container',
                                        'upnp:album_art'  => "http://sc.cuevana.tv/box/$serie.jpg"
                                );

	}
    }

    function showSerieSeasonEpisodes(&$items, $season) {
	preg_match_all("/<li .*listSeries.*,\"([0-9]+)\".*nume.*>(.*)<\/.*>(.*)<\//U",file_get_contents("http://www.cuevana.tv/list_search_id.php?temporada=$season"), $episodes, PREG_SET_ORDER);
	foreach($episodes as $episode) {
		$items[] = array(
                                        'id'              => "umsp://plugins/cuevana?episode=$episode[1]",
                                        'parentID'        => "umsp://plugins/cuevana?season=$season",
                                        'dc:title'        => $episode[2].$episode[3],
                                        'upnp:class'      => 'object.container',
                                        'upnp:album_art'  => ""
                                );

	}

    }

    function watch(&$items, $id, $tipo) {
	preg_match("/goSource\('(.*)','megaupload'\);/",file_get_contents("http://www.cuevana.tv/player/source?id=$id&subs=,ES&onstart=yes&tipo=$tipo&sub_pre=ES"),$info);
	
	$res = http_post("http://www.cuevana.tv/player/source_get", array("key"=>$info[1], "host"=>"megaupload", "id"=>$id, "subs"=>",ES", "tipo"=>"s&sub_pre=ES"));

	preg_match("/downloadlink.*href=\"(.*)\"/U",file_get_contents(substr($res["content"],3)),$stream);

	$items[] = array (
		'id'           => 'umsp://plugins/cuevana?watch=$id',
		'dc:title'     => 'start watching',
		'res'          => $stream[1],
		'upnp:class'   => 'object.item.videoItem',
		'protocolInfo' => 'http-get:*:*:*'
	     );
	sleep(45);
    }
	function http_post ($url, $data)
	{
	    $data_url = http_build_query ($data);
	    $data_len = strlen ($data_url);

	    return array ('content'=>file_get_contents ($url, false, stream_context_create (array ('http'=>array ('method'=>'POST'
		    , 'header'=>"Connection: close\r\nContent-Length: $data_len\r\n"
		    , 'content'=>$data_url
		    ))))
		, 'headers'=>$http_response_header
		);
	}
    function showMoviesMenu(&$items, $page) {
	preg_match_all("/.*<td valign='top'><a href='(.*)'>.*<img src='(.*)'.*<div class='tit'>.*<a href.*>(.*)<\/a>/sU",file_get_contents("http://www.cuevana.tv/peliculas/lista?page=$page"), $pelis, PREG_SET_ORDER);
	preg_match("/<span class='pagination'>.*<a href='page=.*'>(.*)<\/a>.*<a class='next'/s",file_get_contents("http://www.cuevana.tv/peliculas/lista?page=$page"), $paginas);;
	$max = $paginas[1];
	if($page>1) {
		$items[] = array(
                                        'id'              => "umsp://plugins/cuevana?page=".($page-1),
                                        'parentID'        => "umsp://plugins/cuevana?page=$page",
                                        'dc:title'        => "<<-- pagina anterior",
                                        'upnp:class'      => 'object.container',
                                        'upnp:album_art'  => ""
                                );
	}
	foreach($pelis as $peli) {
		$chunks = split("/", $peli[1]);
		$items[] = array(
                                        'id'              => "umsp://plugins/cuevana?movie=".$chunks[2],
                                        'parentID'        => "umsp://plugins/cuevana?page=$page",
                                        'dc:title'        => $peli[3],
                                        'upnp:class'      => 'object.container',
                                        'upnp:album_art'  => $peli[2]
                                );
	
	}
	if($page<$max) {
		$items[] = array(
                                        'id'              => "umsp://plugins/cuevana?page=".($page+1),
                                        'parentID'        => "umsp://plugins/cuevana?page=$page",
                                        'dc:title'        => "pagina siguiente -->>",
                                        'upnp:class'      => 'object.container',
                                        'upnp:album_art'  => ""
                                );
	}
    }
?>
