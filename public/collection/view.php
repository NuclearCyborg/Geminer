<?php
require_once $_SERVER['DOCUMENT_ROOT']."/../start.php";
gen_top("A gem collection");
require_once $_SERVER['DOCUMENT_ROOT']."/../fn/get_collection.php";
require_once $_SERVER['DOCUMENT_ROOT']."/../fn/user_button.php";

$sth = $dbh->prepare("SELECT COUNT(*) FROM collection_ratings WHERE collection = ? AND is_positive = 1");
$sth->execute([$collection['id']]);
$positive_rating_amount = $sth->fetchColumn();
$sth = $dbh->prepare("SELECT COUNT(*) FROM collection_ratings WHERE collection = ? AND is_positive = 0");
$sth->execute([$collection['id']]);
$negative_rating_amount = $sth->fetchColumn();

$sth = $dbh->prepare("SELECT (SELECT COUNT(1) FROM collections_by_score s WHERE c.score <= s.score) FROM collections_by_score c WHERE id = ?");
$sth->execute([$collection['id']]);
$leaderboard_position = $sth->fetchColumn();

$user_rating = null;
if ($is_logged_in) {
    $sth = $dbh->prepare("SELECT is_positive FROM collection_ratings WHERE collection = ? AND rater = ?");
    $sth->execute([$collection['id'], $user['id']]);
    $results = $sth->fetchAll(PDO::FETCH_ASSOC);
    if (count($results) > 0)
        $user_rating = ($results[0]['is_positive'] ? true : false);
}
?>
<?=user_background($collection['by'])?>
<h1><?=htmlentities($collection['name'])?></h1>
<p>by <?php user_button($collection['by']); ?></p>
<img class="collection-img fill-page" id="collectionImg">
<script>
(async ()=> {
    await gemsInfo;
    let data = JSON.parse("<?=$collection['data']?>");
    let height = data.length;
    let width = data[0].length;
    const tileSize = 16;
    let canvas = $(`<canvas width=${width * tileSize} height=${height * tileSize}></canvas>`)[0];
    let context = canvas.getContext("2d");
    
    for (let y=0; y<height; y++) {
        for (let x=0; x<width; x++) {
            await (new Promise(async (res, rej) => {
                let gem = gemsInfo[data[y][x]];
                
                if (gem.type == "colour") {
                    context.fillStyle = "#"+gem.colour;
                    context.fillRect(x * tileSize, y * tileSize, tileSize, tileSize);
                    res();
                } else if (gem.type == "image") {
                    let gemImage = new Image();
                    gemImage.onload = () => {
                        context.drawImage(gemImage, x * tileSize, y * tileSize, tileSize, tileSize);
                        res();
                    }
                    gemImage.src = `/a/i/gem/${gem.id}.png`;
                }
            }));
        }
    }
    $("#collectionImg").attr("src", canvas.toDataURL());
})()
</script>
<hr>
<a id="rate-1" class="btn btn-light text-dark"<?php if ($is_logged_in) { ?> onclick="rate(1)"<?php } else { ?> href="/log/in.php"<?php } ?> value="<?=$user_rating === true ? "true" : "false"?>">
    <img src="/a/i/ratings/<?=$user_rating === true ? "" : "in"?>active/positive.png" height=30>
    <?=$positive_rating_amount?>
</a>

<a id="rate-0" class="btn btn-light text-dark"<?php if ($is_logged_in) { ?> onclick="rate(0)"<?php } else { ?> href="/log/in.php"<?php } ?> value="<?=$user_rating === false ? "true" : "false"?>">
    <img src="/a/i/ratings/<?=$user_rating === false ? "" : "in"?>active/negative.png" height=30>
    <?=$negative_rating_amount?>
</a>

<span style="font-size:2em">= <?=$positive_rating_amount - $negative_rating_amount?></span>
<span>, putting it in position <b><?=$leaderboard_position?></b> on the <a href="/collection/leaderboard.php">leaderboard</a>.</span>

<script>
    function rate(isPositive) {
        let collectionId = parseInt(new URLSearchParams(window.location.search).get("id"), 16);

        if ($(`#rate-${isPositive}`).attr("value") == "false")
            $.post("/api/do/rate-collection/add.php", {
                collection: collectionId,
                is_positive: isPositive
            }, () => location.reload());
        else
            $.post("/api/do/rate-collection/remove.php", {
                collection: collectionId
            }, () => location.reload());
    }
</script>

<?php if ($is_logged_in and $collection['by'] == $user['id']) { ?>
<hr>
<h2>This collection is yours.</h2>
<a href="/collection/edit.php?id=<?=$_GET['id']?>" class="btn btn-primary">Edit</a>
<br>
<?php if (!$collection['is_pfp'] and $collection['type'] == 0) { ?>
<form action="/collection/make_pfp.php" method="post">
    <button class="btn btn-primary" name="id" value="<?=$collection['id']?>">Make this collection your profile picture</button>
</form>
<?php } else { ?>
<button class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="<?php if ($collection['is_pfp']) { ?>This collection is already your profile picture.<?php } else if ($collection['type'] != 0) { ?>You can only make standard square collections your profile picture.<?php } ?>" disabled>Make this collection your profile picture</button>
<br>
<?php } if ($collection['is_pfp'] or $collection['type'] == 3) { ?>
<button class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php if ($collection['is_pfp']) { ?>You can't delete your profile picture. Make another standard square collection your profile picture and then come here to delete this.<?php } else if ($collection['type'] == 3) { ?>You only get one massive collection, so you can't delete it.<?php } ?>" disabled>Delete</button>
<?php } else { ?>
<button class="btn btn-danger" type="submit" name="id" value="<?=$collection['id']?>" onclick="$('#confirmCollectionDelete').modal()">Delete</button>

<div class="modal fade" id="confirmCollectionDelete" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Really delete?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Do you really want to delete this collection? You will get your gems back.</p>
            </div>
            <div class="modal-footer">
                <form class="form-inline" action="/collection/delete.php" method="post">
                    <button class="btn btn-danger" type="submit" name="id" value="<?=$collection['id']?>">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php }}gen_bottom(); ?>