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
    self::P2CCreateCommunityCategory();
    self::P2CCreateCommunity();
    self::P2CSyncMember();
    //FIXME cleanup function 
  }
  public static function P2CCreateCommunityCategory(){
    $cfg_p2cc = unserialize(Doctrine::getTable("SnsConfig")->get("zuniv_us_profile2community_category"));

    print_r($cfg_p2cc);
    $profile_list = Doctrine_Query::create()->from('Profile p,p.Translation t')->where("p.name NOT LIKE ?","op_preset%")->fetchArray();
    print_r($profile_list);
    
    foreach($profile_list as $profile){
      if(@$cfg_p2cc[$profile["id"]]){
        echo "exists. update name... \n"; //exist community category. sync name
        $obj = Doctrine::getTable("CommunityCategory")->find($cfg_p2cc[$profile["id"]]);
      }else{
        echo "create community catgeory."; // new community catgegory
        $obj = new CommunityCategory();
        $obj->is_allow_member_community = 0;
        $cc = Doctrine_Query::create()->from('CommunityCategory cc')->where('cc.level = ?', 0)->fetchOne(); //FIXME fetch default category.
        $obj->tree_key = $cc["tree_key"];
      }
      $obj->name = $profile["Translation"]["ja_JP"]["caption"];
      $obj->save();
      $cfg_p2cc[$profile["id"]] = $obj->id;
    }
    Doctrine::getTable("SnsConfig")->set("zuniv_us_profile2community_category",serialize($cfg_p2cc));
  }
  public static function P2CCreateCommunity(){
    $cfg_po2c = unserialize(Doctrine::getTable("SnsConfig")->get("zuniv_us_profile_option2community"));
    $po_list = Doctrine_Query::create()->from('ProfileOption po,po.Translation t')->fetchArray();
    foreach($po_list as $po){
      print_r($po["id"]);
      print_r($po["Translation"]["ja_JP"]["value"]);
      
      if(@$cfg_po2c[$po["id"]]){
        $c = octrine::getTable("Community")->find($cfg_po2c[$po["id"]]) ;
        $c->name = $po["Translation"]["ja_JP"]["value"];
        $c->save();
      }else{
        $c = new Community();
        $cfg_p2cc = unserialize(Doctrine::getTable("SnsConfig")->get("zuniv_us_profile2community_category"));
        $c->community_category_id =  $cfg_p2cc[$po["profile_id"]];
        $c->name = $po["Translation"]["ja_JP"]["value"];
        $c->save();

        $cc = new CommunityConfig();
        $cc->community_id = $c->id;
        $cc->name = 'public_flag';
        $cc->value = 'public';
        $cc->save();

        $cc = new CommunityConfig();
        $cc->community_id = $c->id;
        $cc->name = 'public_authority';
        $cc->value = 'public';
        $cc->save();
        
        $cc = new CommunityConfig();
        $cc->community_id = $c->id;
        $cc->name = 'register_policy';
        $cc->value = 'close';
        $cc->save();
        
        $cc = new CommunityConfig();
        $cc->community_id = $c->id;
        $cc->name = 'description';
        $cc->value = 'auto generated community';
        $cc->save();

        $cc = new CommunityConfig();
        $cc->community_id = $c->id;
        $cc->name = 'is_send_pc_joinCommunity_mail';
        $cc->value = 1;
        $cc->save();

        //root
        $cm = new CommunityMember();
        $cm->community_id = $c->id;
        $cm->member_id = 1;
        $cm->save();
        $cmp = new CommunityMemberPosition();
        $cmp->community_id = $c->id;
        $cmp->member_id = 1;
        $cmp->community_member_id = $cm->id;
        $cmp->name = "admin";
        $cmp->save();
      }
      
      $cfg_po2c[$po["id"]] = $c->id;
    }

    Doctrine::getTable("SnsConfig")->set("zuniv_us_profile_option2community",serialize($cfg_po2c));
  }
  public static function P2CSyncMember(){
    $cfg_po2c = unserialize(Doctrine::getTable("SnsConfig")->get("zuniv_us_profile_option2community"));
    foreach($cfg_po2c as $profile_option_id => $community_id){
      $mp_list = Doctrine_Query::create()->from('MemberProfile mp')->where("profile_option_id = ?",$profile_option_id)->fetchArray();
      foreach($mp_list as $mp){
        $is_member = Doctrine_Query::create()->from("CommunityMember mc")->where("mc.member_id = ?",$mp["member_id"])->fetchOne();
        if($is_member){
          echo "you are a member. skip\n"; //skip
        }else{
          //create CommunityMember record;
          $cm = new CommunityMember();
          $cm->community_id = $community_id;
          $cm->member_id = $mp["member_id"];
          $cm->save();
        }
      }
    }  
  }
}
