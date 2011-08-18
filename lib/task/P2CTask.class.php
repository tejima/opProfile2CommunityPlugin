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
    $this->name             = 'P2C';
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
      $c_list = Doctrine_Query::create()->from("Community c")->where("c.name = ?",$po["Translation"]["ja_JP"]["value"])->fetchArray();
      $c = @$c_list[0];
      if(!$c){
        echo "NO Community.name matched.\n";
      }else{
        $mp_list = Doctrine_Query::create()->from("MemberProfile mp")->where("mp.profile_option_id = ?",$po["id"])->fetchArray();
        
        Doctrine_Query::create()->delete()->from("CommunityMember cm")->where("cm.community_id = ?",$c["id"])->execute();
        Doctrine_Query::create()->delete()->from("CommunityMemberPosition cmp")->where("cmp.community_id = ?",$c["id"])->execute();
        
        echo "Community member cleared.\n";


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
        
        foreach($mp_list as $mp){
          $member_id = $mp["member_id"];
          $cm = new CommunityMember();
          $cm->community_id = $c["id"];
          $cm->member_id = $member_id;
          $cm->save();
        }        
      }
    }
  }
}
