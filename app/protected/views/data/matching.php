<script src="/js/levenshtein.js" type="text/javascript"></script>
<script src="/js/doublemetaphone.js" type="text/javascript"></script>
<script>
alters1 = <?php echo json_encode($alters1); ?>;
alters2 = <?php echo json_encode($alters2); ?>;
answers = <?php echo json_encode($answers); ?>;
prompts = <?php echo json_encode($prompts); ?>;
studyId = <?php echo $study->id; ?>;

altersD = new Object;
altersL = new Object;
altersLId = new Object;
altersDId = new Object;

dm = new DoubleMetaphone;
dm.maxCodeLen = 64;
for(j in alters1){
    altersL[j] = 999;
    altersD[j] = 999;
    for(k in alters2){
        ls = new Levenshtein(alters1[j], alters2[k]);
        if(ls.distance < altersL[j]){
            altersL[j] = ls.distance;
            altersLId[j] = k;
        }
        d1 = dm.doubleMetaphone(alters1[j]).primary;
        d2 = dm.doubleMetaphone(alters2[k]).primary;
        ls = new Levenshtein(d1, d2);
        if(ls.distance < altersD[j]){
            altersD[j] = ls.distance;
            altersDId[j] = k;
        }
    }

}
function autoMatch(){
    $(".aMatch").each(function(){
        var id = $(this).attr("id");
        var lTol = altersL[id];
        var dTol = altersD[id];
        var lId = altersLId[id];
        var dId = altersDId[id];
        if($(".unMatch-" + id).length == 0){
            if(lTol <= $("#lTol").val() && dTol <= $("#dTol").val()){
                $(this).val(dId);
                //$("#"  + id + "-name").val(alters2[dId]);
                //$(this).parent().next().attr("alterId",$(this).val());
            }else{
                $(this).val("");
                //$("#"  + id + "-name").val("");
                //$(this).parent().next().attr("alterId",$(this).val());
                $(this).parent().next().html("");
            }
            $(this).change();
        }
    });
    loadR($("#question").val());
}
function loadR(questionId){
    if(!questionId)
        return false;
    $(".responses").each(function(){
        $(this).html(answers[questionId][$(this).attr("alterId")]);
    });
}

function matchUp(s){
    var id = $(s).attr("id");
    var id2 = $(s).val();
    $(s).parent().next().attr("alterId",$(s).val());
    if($(s).val() != ""){
        $("#" + id + "-name").val($("option:selected", s).text());
        $("#" + id + "-buttons").html("<button class='btn btn-xs btn-success' onclick='save(" + studyId + "," +id + "," + id2 +")'>save</button>");;
    }else{
        $("#" + id + "-name").val("");
    }
    loadR($("#question").val());

}
function save(sId, id1, id2){
    var alterName = $("#" + id1 + "-name").val();
    $.post("/data/savematch", {studyId:sId, alterId1:id1, alterId2:id2, matchedName: alterName, <?php echo Yii::app()->request->csrfTokenName . ':"' . Yii::app()->request->csrfToken . '"' ?>}, function(data){
        $("#" + id1 + "-buttons").html(data);
    })
}

function unMatch(id1, id2){
    $.post("/data/unmatch", {alterId1:id1, alterId2:id2, <?php echo Yii::app()->request->csrfTokenName . ':"' . Yii::app()->request->csrfToken . '"' ?>}, function(data){
        $("#" + id1 + "-buttons").html("");
        $("#" + id1 + "-name").val("");
        $("#" + id1).val("");
        $("#" + id1).change();
    })
}
</script>
<div class="panel panel-success">
    <div class="panel-heading">
        Automatic Matching
    </div>

    <div class="panel-body">
        <div class="form-group">
            <label class="control-label col-lg-1">Metaphone Tolerence</label>
            <div class="col-lg-3">
            <input class="form-control" id="dTol" type="number" value="2">
            </div>
            <label class="control-label col-lg-1">Levenshtein Tolerence</label>
            <div class="col-lg-3">
                <input class="form-control" id="lTol" type="number" value="5">
            </div>
            <div class="col-lg-4">
                <button class="btn btn-primary" onclick="autoMatch();">Match</button>
            </div>
        </div>
    </div>
</div>
<div class="panel panel-warning">
<div class="panel-heading">
    <?php
    echo CHtml::dropdownlist(
        'question',
        '',
        $questions,
        array('empty' => 'Choose Question', "class"=>"pull-left","onChange"=>'loadR($(this).val());$("#prompt").html(prompts[$(this).val()])')
    );
    ?>
    <div id="prompt">Display Alter Question Response</div>
    </div>
</div>


<table class="table table-condensed">
    <tr>
        <th>Interview 1</th>
        <th>Responses</th>

        <th>Interview 2</th>
        <th>Responses</th>

        <th>Matched Alter name</th>
    </tr><?php foreach($alters1 as $alterId=>$alter): ?>

    <tr>
        <td><?php echo $alter; ?></td>
        <td class="responses" alterId=<?php echo $alterId; ?>></td>
        <td><?php
            foreach($alters2 as $aid=>$name)
                $alterIds2[] = $aid;
            
            $match = MatchedAlters::model()->findByAttributes(array("alterId1"=>$alterId),
            
            array("condition"=>"alterId2 IN (" . implode(",", $alterIds2). ")"));
            if($match){
                $selected = $match->alterId2;
                $selectedName = $match->matchedName;
            }else{
                $selected = "";
                $selectedName = "";
            }
                    if(count($alters2) > 0){
                        echo CHtml::dropdownlist(
                            'alterId2',
                            $selected,
                            $alters2,
                            array('empty' => 'No Match', "class"=>"aMatch", "id"=>$alterId, "onChange"=>'matchUp(this)')
                        );
                    }
                ?></td>
        <td class="responses" alterId=<?php echo $selected; ?>></td>
        <td><?php echo CHtml::textField("name",$selectedName ,array("id"=>$alterId."-name")); ?></td>
        <td id="<?php echo $alterId; ?>-buttons">
            <?php
                if(isset($match))
                    echo "<button class='btn btn-xs btn-danger unMatch-$alterId' onclick='unMatch($alterId, $selected)'>Unmatch</button>";
            ?>
            
        </td>
    </tr><?php endforeach; ?>
</table>
