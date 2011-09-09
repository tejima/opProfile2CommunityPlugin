<?php
//FIXME description of this source
class P2CTask extends sfBaseTask
{
  protected function configure()
  {
    set_time_limit(120);
    mb_language("Japanese");
    mb_internal_encoding("utf-8");
    $this->namespace        = 'zuniv.us';
    $this->name             = 'Profile2Community';
    $this->aliases          = array('zu-p2c');
    $this->briefDescription = '';
  }
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);
    self::P2CSyncMember();
  }
  public static function P2CSyncMember(){
    $po_list = Doctrine_Query::create()->from('ProfileOption po,po.Translation t')->where("t.lang = ?","ja_JP")->fetchArray();
    foreach($po_list as $po){
      echo $po["Translation"]["ja_JP"]["value"];
      echo "\n";
      $c = Doctrine_Query::create()->from("Community c")->where("c.name = ?",$po["Translation"]["ja_JP"]["value"])->fetchOne(array(),Doctrine::HYDRATE_ARRAY);
      if(!$c){
        echo "NO COMMUNITY.NAME MATCHED.\n";
      }else{
        /*
        Doctrine_Query::create()->delete()->from("CommunityMember cm")->where("cm.community_id = ?",$c["id"])->execute();
        Doctrine_Query::create()->delete()->from("CommunityMemberPosition cmp")->where("cmp.community_id = ?",$c["id"])->execute();
        echo "COMMUNITY MEMBER CLEARED.\n";
        
        //member_id = 1 is the owner. 
       
        $cm = new CommunityMember();
        $cm->community_id = $c["id"];
        $cm->member_id = 1;
        $cm->save();

        $cmp = new CommunityMemberPosition();
        $cmp->community_id = $c["id"];
        $cmp->member_id = 1;
        $cmp->community_member_id = $cm->id;
        $cmp->name = "admin";
        $cmp->save();
        echo "COMMUNITY ADMIN ADDED.\n";
        */

        $mp_list = Doctrine_Query::create()->from("MemberProfile mp")->where("mp.profile_option_id = ?",$po["id"])->fetchArray();
        //入っているべきでない人を追い出す
        $notin = array("1");
        foreach($mp_list as $mp){
          $notin[] = $mp["member_id"];
        }
        Doctrine_Query::create()->delete()->from("CommunityMember cm")->where("cm.community_id = ?",$c["id"])->andWhereNotIn("cm.member_id",$notin)->execute();
        Doctrine_Query::create()->delete()->from("CommunityMemberPosition cmp")->where("cmp.community_id = ?",$c["id"])->andWhereNotIn("cmp.member_id",$notin)->execute();
        foreach($mp_list as $mp){
          $_cm = Doctrine_Query::create()->from("CommunityMember cm")->where("cm.member_id = ?",$mp["member_id"])->addWhere("cm.community_id = ?",$c["id"])->fetchOne(array(),Doctrine::HYDRATE_ARRAY);
          if($_cm){
            //skip
            echo "  MEMBER_ID=${mp['member_id']} IS A MEMBER OF COMMUNITY_ID=${c['id']} SKIP.\n";
          }else{
            $cm = new CommunityMember();
            $cm->community_id = $c["id"];
            $cm->member_id = $mp["member_id"];
            $cm->save();
            echo "  MEMBER_ID=${mp['member_id']} ADDED TO COMMUNITY_ID=${c['id']}.\n";
          }
        }        
      }
    }
  }
}
