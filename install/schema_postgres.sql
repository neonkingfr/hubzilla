CREATE TABLE "abconfig" (
  "id" serial  NOT NULL,
  "chan" bigint NOT NULL DEFAULT '0',
  "xchan" text NOT NULL,
  "cat" text NOT NULL,
  "k" text NOT NULL,
  "v" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "abconfig_chan" on abconfig ("chan");
create index "abconfig_xchan" on abconfig ("xchan");
create index "abconfig_cat" on abconfig ("cat");
create index "abconfig_k" on abconfig ("k");
CREATE TABLE "abook" (
  "abook_id" serial  NOT NULL,
  "abook_account" bigint  NOT NULL,
  "abook_channel" bigint  NOT NULL,
  "abook_xchan" text NOT NULL DEFAULT '',
  "abook_my_perms" bigint NOT NULL,
  "abook_their_perms" bigint NOT NULL,
  "abook_closeness" numeric(3)  NOT NULL DEFAULT '99',
  "abook_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_connected" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_dob" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "abook_flags" bigint NOT NULL DEFAULT '0',
  "abook_blocked" smallint NOT NULL DEFAULT '0',
  "abook_ignored" smallint NOT NULL DEFAULT '0',
  "abook_hidden" smallint NOT NULL DEFAULT '0',
  "abook_archived" smallint NOT NULL DEFAULT '0',
  "abook_pending" smallint NOT NULL DEFAULT '0',
  "abook_unconnected" smallint NOT NULL DEFAULT '0',
  "abook_self" smallint NOT NULL DEFAULT '0',
  "abook_feed" smallint NOT NULL DEFAULT '0',
  "abook_not_here" smallint NOT NULL DEFAULT '0',
  "abook_profile" varchar(64) NOT NULL DEFAULT '',
  "abook_incl" TEXT NOT NULL DEFAULT '',
  "abook_excl" TEXT NOT NULL DEFAULT '',
  "abook_instance" TEXT NOT NULL DEFAULT '',
  "abook_role" varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY ("abook_id")
);
  create index  "abook_account" on abook ("abook_account");
  create index  "abook_channel" on abook  ("abook_channel");
  create index  "abook_xchan"  on abook ("abook_xchan");
  create index  "abook_my_perms"  on abook ("abook_my_perms");
  create index  "abook_their_perms"  on abook ("abook_their_perms");
  create index  "abook_closeness" on abook  ("abook_closeness");
  create index  "abook_created"  on abook ("abook_created");
  create index  "abook_updated"  on abook ("abook_updated");
  create index  "abook_flags"  on abook ("abook_flags");
  create index  "abook_blocked"  on abook ("abook_blocked");
  create index  "abook_ignored"  on abook ("abook_ignored");
  create index  "abook_hidden"  on abook ("abook_hidden");
  create index  "abook_archived"  on abook ("abook_archived");
  create index  "abook_pending"  on abook ("abook_pending");
  create index  "abook_unconnected"  on abook ("abook_unconnected");
  create index  "abook_self"  on abook ("abook_self");
  create index  "abook_feed"  on abook ("abook_feed");
  create index  "abook_not_here"  on abook ("abook_not_here");
  create index  "abook_profile" on abook  ("abook_profile");
  create index  "abook_dob" on abook  ("abook_dob");
  create index  "abook_connected" on abook  ("abook_connected");
  create index  "abook_channel_closeness" on abook ("abook_channel", "abook_closeness");
  create index  "abook_role" on abook ("abook_role");

CREATE TABLE "account" (
  "account_id" serial  NOT NULL,
  "account_parent" bigint  NOT NULL DEFAULT '0',
  "account_default_channel" bigint  NOT NULL DEFAULT '0',
  "account_salt" varchar(32) NOT NULL DEFAULT '',
  "account_password" text NOT NULL DEFAULT '',
  "account_email" text NOT NULL DEFAULT '',
  "account_external" text NOT NULL DEFAULT '',
  "account_language" varchar(16) NOT NULL DEFAULT 'en',
  "account_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_lastlog" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_flags" bigint  NOT NULL DEFAULT '0',
  "account_roles" bigint  NOT NULL DEFAULT '0',
  "account_reset" text NOT NULL DEFAULT '',
  "account_expires" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_expire_notified" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "account_service_class" varchar(32) NOT NULL DEFAULT '',
  "account_level" bigint  NOT NULL DEFAULT '0',
  "account_password_changed" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("account_id")
);
create index "account_email" on account ("account_email");
create index "account_service_class" on account ("account_service_class");
create index "account_parent" on account ("account_parent");
create index "account_flags"  on account ("account_flags");
create index "account_roles"  on account ("account_roles");
create index "account_lastlog"  on account ("account_lastlog");
create index "account_expires"  on account ("account_expires");
create index "account_default_channel"  on account ("account_default_channel");
create index "account_external"  on account ("account_external");
create index "account_level"  on account ("account_level");
create index "account_password_changed"  on account ("account_password_changed");
CREATE TABLE "addon" (
  "id" serial NOT NULL,
  "aname" text NOT NULL,
  "version" text NOT NULL DEFAULT '0',
  "installed" numeric(1) NOT NULL DEFAULT '0',
  "hidden" numeric(1) NOT NULL DEFAULT '0',
  "tstamp" numeric(20) NOT NULL DEFAULT '0',
  "plugin_admin" numeric(1) NOT NULL DEFAULT '0',
  PRIMARY KEY ("id")
);
create index "addon_hidden_idx" on addon ("hidden");
create index "addon_name_idx" on addon ("aname");
create index "addon_installed_idx" on addon ("installed");
CREATE TABLE "app" (
  "id" serial NOT NULL,
  "app_id" text NOT NULL DEFAULT '',
  "app_sig" text NOT NULL DEFAULT '',
  "app_author" text NOT NULL DEFAULT '',
  "app_name" text NOT NULL DEFAULT '',
  "app_desc" text NOT NULL DEFAULT '',
  "app_url" text NOT NULL DEFAULT '',
  "app_photo" text NOT NULL DEFAULT '',
  "app_version" text NOT NULL DEFAULT '',
  "app_channel" bigint NOT NULL DEFAULT '0',
  "app_addr" text NOT NULL DEFAULT '',
  "app_price" text NOT NULL DEFAULT '',
  "app_page" text NOT NULL DEFAULT '',
  "app_requires" text NOT NULL DEFAULT '',
  "app_deleted" smallint NOT NULL DEFAULT '0',
  "app_system" smallint NOT NULL DEFAULT '0',
  "app_plugin" text NOT NULL DEFAULT '',
  "app_options" smallint NOT NULL DEFAULT '0',
  "app_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "app_edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("id")
);
create index "app_id" on app ("app_id");
create index "app_name" on app ("app_name");
create index "app_url" on app ("app_url");
create index "app_photo" on app ("app_photo");
create index "app_version" on app ("app_version");
create index "app_channel" on app ("app_channel");
create index "app_price" on app ("app_price");
create index "app_created" on app ("app_created");
create index "app_edited" on app ("app_edited");
create index "app_deleted" on app ("app_deleted");
create index "app_system" on app ("app_system");


CREATE TABLE "atoken" (
  "atoken_id" serial NOT NULL,
  "atoken_guid" varchar(255) NOT NULL DEFAULT '',
  "atoken_aid" bigint NOT NULL DEFAULT 0,
  "atoken_uid" bigint NOT NULL DEFAULT 0,
  "atoken_name" varchar(255) NOT NULL DEFAULT '',
  "atoken_token" varchar(255) NOT NULL DEFAULT '',
  "atoken_expires" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("atoken_id"));
create index atoken_guid on atoken (atoken_guid);
create index atoken_aid on atoken (atoken_aid);
create index atoken_uid on atoken (atoken_uid);
create index atoken_name on atoken (atoken_name);
create index atoken_token on atoken (atoken_token);
create index atoken_expires on atoken (atoken_expires);

CREATE TABLE "attach" (
  "id" serial  NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL DEFAULT '0',
  "hash" varchar(64) NOT NULL DEFAULT '',
  "creator" varchar(128) NOT NULL DEFAULT '',
  "filename" text NOT NULL DEFAULT '',
  "filetype" varchar(64) NOT NULL DEFAULT '',
  "filesize" bigint  NOT NULL DEFAULT '0',
  "revision" bigint  NOT NULL DEFAULT '0',
  "folder" varchar(64) NOT NULL DEFAULT '',
  "flags" bigint  NOT NULL DEFAULT '0',
  "is_dir" smallint NOT NULL DEFAULT '0',
  "is_photo" smallint NOT NULL DEFAULT '0',
  "os_storage" smallint NOT NULL DEFAULT '0',
  "os_path" text NOT NULL,
  "display_path" text NOT NULL,
  "content" bytea NOT NULL,
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("id")

);
create index "attach_aid_idx" on attach ("aid");
create index "attach_uid_idx" on attach ("uid");
create index "attach_hash_idx" on attach ("hash");
create index "attach_filename_idx" on attach ("filename");
create index "attach_filetype_idx" on attach ("filetype");
create index "attach_filesize_idx" on attach ("filesize");
create index "attach_created_idx" on attach ("created");
create index "attach_edited_idx" on attach ("edited");
create index "attach_revision_idx" on attach ("revision");
create index "attach_folder_idx" on attach ("folder");
create index "attach_flags_idx" on attach ("flags");
create index "attach_is_dir_idx" on attach ("is_dir");
create index "attach_is_photo_idx" on attach ("is_photo");
create index "attach_os_storage_idx" on attach ("os_storage");
create index "attach_creator_idx" on attach ("creator");
CREATE TABLE "auth_codes" (
  "id" varchar(40) NOT NULL,
  "client_id" varchar(20) NOT NULL,
  "redirect_uri" varchar(200) NOT NULL,
  "expires" bigint NOT NULL,
  "auth_scope" varchar(512) NOT NULL,
  PRIMARY KEY ("id")
);
CREATE TABLE "cache" (
  "k" text NOT NULL,
  "v" text NOT NULL,
  "updated" timestamp NOT NULL,
  PRIMARY KEY ("k")
);
CREATE TABLE "cal" (
  "cal_id" serial  NOT NULL,
  "cal_aid" bigint NOT NULL DEFAULT '0',
  "cal_uid" bigint NOT NULL DEFAULT '0',
  "cal_hash" text NOT NULL,
  "cal_name" text NOT NULL,
  "uri" text NOT NULL,
  "logname" text NOT NULL,
  "pass" text NOT NULL,
  "ctag" text NOT NULL,
  "synctoken" text NOT NULL,
  "cal_types" text NOT NULL DEFAULT '0',
  PRIMARY KEY ("cal_id")
);
create index "cal_hash_idx" on cal ("cal_hash");
create index "cal_name_idx" on cal ("cal_name");
create index "cal_types_idx" on cal ("cal_types");
create index "cal_aid_idx" on cal ("cal_aid");
create index "cal_uid_idx" on cal ("cal_uid");

CREATE TABLE "channel" (
  "channel_id" serial  NOT NULL,
  "channel_account_id" bigint  NOT NULL DEFAULT '0',
  "channel_primary" numeric(1)  NOT NULL DEFAULT '0',
  "channel_name" text NOT NULL DEFAULT '',
  "channel_address" text NOT NULL DEFAULT '',
  "channel_guid" text NOT NULL DEFAULT '',
  "channel_guid_sig" text NOT NULL,
  "channel_hash" text NOT NULL DEFAULT '',
  "channel_portable_id" text NOT NULL DEFAULT '',
  "channel_timezone" varchar(128) NOT NULL DEFAULT 'UTC',
  "channel_location" text NOT NULL DEFAULT '',
  "channel_theme" text NOT NULL DEFAULT '',
  "channel_startpage" text NOT NULL DEFAULT '',
  "channel_pubkey" text NOT NULL,
  "channel_prvkey" text NOT NULL,
  "channel_notifyflags" bigint  NOT NULL DEFAULT '65535',
  "channel_pageflags" bigint  NOT NULL DEFAULT '0',
  "channel_dirdate" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "channel_lastpost" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "channel_deleted" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "channel_active" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "channel_max_anon_mail" bigint  NOT NULL DEFAULT '10',
  "channel_max_friend_req" bigint  NOT NULL DEFAULT '10',
  "channel_expire_days" bigint NOT NULL DEFAULT '0',
  "channel_passwd_reset" text NOT NULL DEFAULT '',
  "channel_default_group" text NOT NULL DEFAULT '',
  "channel_allow_cid" text ,
  "channel_allow_gid" text ,
  "channel_deny_cid" text ,
  "channel_deny_gid" text ,
  "channel_removed" smallint NOT NULL DEFAULT '0',
  "channel_system" smallint NOT NULL DEFAULT '0',
  "channel_moved" text NOT NULL DEFAULT '',
  "channel_password" varchar(255) NOT NULL,
  "channel_salt" varchar(255) NOT NULL,
  PRIMARY KEY ("channel_id"),
  UNIQUE ("channel_address")
);
create index "channel_account_id" on channel ("channel_account_id");
create index "channel_primary" on channel ("channel_primary");
create index "channel_name" on channel ("channel_name");
create index "channel_timezone" on channel ("channel_timezone");
create index "channel_location" on channel ("channel_location");
create index "channel_theme" on channel ("channel_theme");
create index "channel_notifyflags" on channel ("channel_notifyflags");
create index "channel_pageflags" on channel ("channel_pageflags");
create index "channel_max_anon_mail" on channel ("channel_max_anon_mail");
create index "channel_max_friend_req" on channel ("channel_max_friend_req");
create index "channel_default_gid" on channel ("channel_default_group");
create index "channel_guid" on channel ("channel_guid");
create index "channel_hash" on channel ("channel_hash");
create index "channel_portable_id" on channel ("channel_portable_id");
create index "channel_expire_days" on channel ("channel_expire_days");
create index "channel_deleted" on channel ("channel_deleted");
create index "channel_active" on channel ("channel_active");
create index "channel_dirdate" on channel ("channel_dirdate");
create index "channel_lastpost" on channel ("channel_lastpost");
create index "channel_removed" on channel ("channel_removed");
create index "channel_system" on channel ("channel_system");
create index "channel_moved" on channel ("channel_moved");
CREATE TABLE "chat" (
  "chat_id" serial  NOT NULL,
  "chat_room" bigint  NOT NULL DEFAULT '0',
  "chat_xchan" text NOT NULL DEFAULT '',
  "chat_text" text NOT NULL,
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("chat_id")
);
create index "chat_room_idx" on chat ("chat_room");
create index "chat_xchan_idx" on chat ("chat_xchan");
create index "chat_created_idx" on chat ("created");
CREATE TABLE "chatpresence" (
  "cp_id" serial  NOT NULL,
  "cp_room" bigint  NOT NULL DEFAULT '0',
  "cp_xchan" text NOT NULL DEFAULT '',
  "cp_last" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "cp_status" text NOT NULL,
  "cp_client" varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY ("cp_id")
);
create index "cp_room" on chatpresence ("cp_room");
create index "cp_xchan" on chatpresence  ("cp_xchan");
create index "cp_last" on chatpresence ("cp_last");
create index "cp_status" on chatpresence ("cp_status");

CREATE TABLE "chatroom" (
  "cr_id" serial  NOT NULL,
  "cr_aid" bigint  NOT NULL DEFAULT '0',
  "cr_uid" bigint  NOT NULL DEFAULT '0',
  "cr_name" text NOT NULL DEFAULT '',
  "cr_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "cr_edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "cr_expire" bigint  NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("cr_id")
);
create index "cr_aid" on chatroom ("cr_aid");
create index "cr_uid" on chatroom ("cr_uid");
create index "cr_name" on chatroom ("cr_name");
create index "cr_created" on chatroom ("cr_created");
create index "cr_edited" on chatroom ("cr_edited");
create index "cr_expire" on chatroom ("cr_expire");
CREATE TABLE "clients" (
  "client_id" varchar(20) NOT NULL,
  "pw" varchar(20) NOT NULL,
  "redirect_uri" varchar(200) NOT NULL,
  "clname" text,
  "icon" text,
  "uid" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("client_id")
);
CREATE TABLE "config" (
  "id" serial  NOT NULL,
  "cat" text  NOT NULL,
  "k" text  NOT NULL,
  "v" text NOT NULL,
  PRIMARY KEY ("id"),
  UNIQUE ("cat","k")
);

CREATE TABLE IF NOT EXISTS "dreport" (
  "dreport_id" serial NOT NULL,
  "dreport_channel" int NOT NULL DEFAULT '0',
  "dreport_mid" varchar(255) NOT NULL DEFAULT '',
  "dreport_site" varchar(255) NOT NULL DEFAULT '',
  "dreport_recip" varchar(255) NOT NULL DEFAULT '',
  "dreport_result" varchar(255) NOT NULL DEFAULT '',
  "dreport_name" varchar(255) NOT NULL DEFAULT '',
  "dreport_time" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "dreport_xchan" varchar(255) NOT NULL DEFAULT '',
  "dreport_queue" varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY ("dreport_id")
);

create index "dreport_mid" on dreport ("dreport_mid");
create index "dreport_site" on dreport ("dreport_site");
create index "dreport_time" on dreport ("dreport_time");
create index "dreport_xchan" on dreport ("dreport_xchan");
create index "dreport_queue" on dreport ("dreport_queue");
create index "dreport_channel" on dreport ("dreport_channel");

CREATE TABLE "event" (
  "id" serial NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint NOT NULL,
  "cal_id" bigint NOT NULL DEFAULT '0',
  "event_xchan" text NOT NULL DEFAULT '',
  "event_hash" text NOT NULL DEFAULT '',
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "dtstart" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "dtend" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "summary" text NOT NULL,
  "description" text NOT NULL,
  "location" text NOT NULL,
  "etype" text NOT NULL,
  "nofinish" numeric(1) NOT NULL DEFAULT '0',
  "adjust" numeric(1) NOT NULL DEFAULT '1',
  "dismissed" numeric(1) NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  "event_status" varchar(255) NOT NULL DEFAULT '',
  "event_status_date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "event_percent" smallint NOT NULL DEFAULT '0',
  "event_repeat" text NOT NULL,
  "event_sequence" smallint NOT NULL DEFAULT '0',
  "event_priority" smallint NOT NULL DEFAULT '0',
  "event_vdata" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "event_uid_idx" on event ("uid");
create index "event_cal_idx" on event ("cal_id");
create index "event_etype_idx" on event ("etype");
create index "event_dtstart_idx" on event ("dtstart");
create index "event_dtend_idx" on event ("dtend");
create index "event_adjust_idx" on event ("adjust");
create index "event_nofinish_idx" on event ("nofinish");
create index "event_dismissed_idx" on event ("dismissed");
create index "event_aid_idx" on event ("aid");
create index "event_hash_idx" on event ("event_hash");
create index "event_xchan_idx" on event ("event_xchan");
create index "event_status_idx" on event ("event_status");
create index "event_sequence_idx" on event ("event_sequence");
create index "event_priority_idx" on event ("event_priority");

CREATE TABLE "pgrp_member" (
  "id" serial  NOT NULL,
  "uid" bigint  NOT NULL,
  "gid" bigint  NOT NULL,
  "xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("id")
);
create index "groupmember_uid" on pgrp_member ("uid");
create index "groupmember_gid" on pgrp_member ("gid");
create index "groupmember_xchan" on pgrp_member ("xchan");

CREATE TABLE "pgrp" (
  "id" serial  NOT NULL,
  "hash" text NOT NULL DEFAULT '',
  "uid" bigint  NOT NULL,
  "visible" numeric(1) NOT NULL DEFAULT '0',
  "deleted" numeric(1) NOT NULL DEFAULT '0',
  "gname" text NOT NULL,
  PRIMARY KEY ("id")

);
create index "groups_uid_idx" on pgrp ("uid");
create index "groups_visible_idx" on pgrp  ("visible");
create index "groups_deleted_idx" on pgrp ("deleted");
create index "groups_hash_idx" on pgrp ("hash");

CREATE TABLE "hook" (
  "id" serial NOT NULL,
  "hook" text NOT NULL,
  "file" text NOT NULL,
  "fn" text NOT NULL,
  "priority" smallint  NOT NULL DEFAULT '0',
  "hook_version" smallint NOT NULL DEFAULT '0',
  PRIMARY KEY ("id")

);
create index "hook_idx" on hook ("hook");
create index "hook_version_idx" on hook ("hook_version");
create index "hook_priority_idx" on hook ("priority");


CREATE TABLE "hubloc" (
  "hubloc_id" serial  NOT NULL,
  "hubloc_guid" text NOT NULL DEFAULT '',
  "hubloc_guid_sig" text NOT NULL DEFAULT '',
  "hubloc_id_url" text NOT NULL DEFAULT '',
  "hubloc_hash" text NOT NULL,
  "hubloc_addr" text NOT NULL DEFAULT '',
  "hubloc_network" text NOT NULL DEFAULT '',
  "hubloc_flags" bigint  NOT NULL DEFAULT '0',
  "hubloc_status" bigint  NOT NULL DEFAULT '0',
  "hubloc_url" text NOT NULL DEFAULT '',
  "hubloc_url_sig" text NOT NULL DEFAULT '',
  "hubloc_site_id" text NOT NULL DEFAULT '',
  "hubloc_host" text NOT NULL DEFAULT '',
  "hubloc_callback" text NOT NULL DEFAULT '',
  "hubloc_connect" text NOT NULL DEFAULT '',
  "hubloc_sitekey" text NOT NULL DEFAULT '',
  "hubloc_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "hubloc_connected" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "hubloc_primary" smallint NOT NULL DEFAULT '0',
  "hubloc_orphancheck" smallint NOT NULL DEFAULT '0',
  "hubloc_error" smallint NOT NULL DEFAULT '0',
  "hubloc_deleted" smallint NOT NULL DEFAULT '0',
  PRIMARY KEY ("hubloc_id")
);
create index "hubloc_url" on hubloc ("hubloc_url");
create index "hubloc_site_id" on hubloc ("hubloc_site_id");
create index "hubloc_guid" on hubloc ("hubloc_guid");
create index "hubloc_hash" on hubloc ("hubloc_hash");
create index "hubloc_id_url" on hubloc ("hubloc_id_url");
create index "hubloc_flags" on hubloc ("hubloc_flags");
create index "hubloc_connect" on hubloc ("hubloc_connect");
create index "hubloc_host" on hubloc ("hubloc_host");
create index "hubloc_addr" on hubloc ("hubloc_addr");
create index "hubloc_network" on hubloc ("hubloc_network");
create index "hubloc_updated" on hubloc ("hubloc_updated");
create index "hubloc_connected" on hubloc ("hubloc_connected");
create index "hubloc_status" on hubloc ("hubloc_status");
create index "hubloc_primary" on hubloc ("hubloc_primary");
create index "hubloc_orphancheck" on hubloc ("hubloc_orphancheck");
create index "hubloc_error" on hubloc ("hubloc_error");
create index "hubloc_deleted" on hubloc ("hubloc_deleted");
CREATE TABLE "iconfig" (
  "id" serial NOT NULL,
  "iid" bigint NOT NULL DEFAULT '0',
  "cat" text NOT NULL DEFAULT '',
  "k" text NOT NULL DEFAULT '',
  "v" text NOT NULL DEFAULT '',
  "sharing" int NOT NULL DEFAULT '0',
  PRIMARY KEY("id")
);
create index "iconfig_iid" on iconfig ("iid");
create index "iconfig_cat" on iconfig ("cat");
create index "iconfig_k" on iconfig ("k");
create index "iconfig_sharing" on iconfig ("sharing");
CREATE TABLE "issue" (
  "issue_id" serial  NOT NULL,
  "issue_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "issue_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "issue_assigned" text NOT NULL,
  "issue_priority" bigint NOT NULL,
  "issue_status" bigint NOT NULL,
  "issue_component" text NOT NULL,
  PRIMARY KEY ("issue_id")
);
create index "issue_created" on issue ("issue_created");
create index "issue_updated" on issue ("issue_updated");
create index "issue_assigned" on issue ("issue_assigned");
create index "issue_priority" on issue ("issue_priority");
create index "issue_status" on issue ("issue_status");
create index "issue_component" on issue ("issue_component");

CREATE TABLE "item" (
  "id" serial  NOT NULL,
  "uuid" text  NOT NULL DEFAULT '',
  "mid" text  NOT NULL DEFAULT '',
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL DEFAULT '0',
  "parent" bigint  NOT NULL DEFAULT '0',
  "parent_mid" text  NOT NULL DEFAULT '',
  "thr_parent" text NOT NULL DEFAULT '',
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "expires" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "commented" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "received" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "changed" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "comments_closed" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "owner_xchan" text NOT NULL DEFAULT '',
  "author_xchan" text NOT NULL DEFAULT '',
  "source_xchan" text NOT NULL DEFAULT '',
  "mimetype" text NOT NULL DEFAULT '',
  "title" text NOT NULL,
  "summary" text NOT NULL,
  "body" text NOT NULL,
  "html" text NOT NULL,
  "app" text NOT NULL DEFAULT '',
  "lang" varchar(64) NOT NULL DEFAULT '',
  "revision" bigint  NOT NULL DEFAULT '0',
  "verb" text NOT NULL DEFAULT '',
  "obj_type" text NOT NULL DEFAULT '',
  "obj" text NOT NULL,
  "tgt_type" text NOT NULL DEFAULT '',
  "target" text NOT NULL,
  "layout_mid" text NOT NULL DEFAULT '',
  "postopts" text NOT NULL DEFAULT '',
  "route" text NOT NULL DEFAULT '',
  "llink" text NOT NULL DEFAULT '',
  "plink" text NOT NULL DEFAULT '',
  "resource_id" text NOT NULL DEFAULT '',
  "resource_type" varchar(16) NOT NULL DEFAULT '',
  "attach" text NOT NULL,
  "sig" text NOT NULL DEFAULT '',
  "location" text NOT NULL DEFAULT '',
  "coord" text NOT NULL DEFAULT '',
  "public_policy" text NOT NULL DEFAULT '',
  "comment_policy" text NOT NULL DEFAULT '',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  "item_restrict" bigint NOT NULL DEFAULT '0',
  "item_flags" bigint NOT NULL DEFAULT '0',
  "item_private" numeric(4) NOT NULL DEFAULT '0',
  "item_unseen" smallint NOT NULL DEFAULT '0',
  "item_wall" smallint NOT NULL DEFAULT '0',
  "item_origin" smallint NOT NULL DEFAULT '0',
  "item_starred" smallint NOT NULL DEFAULT '0',
  "item_uplink" smallint NOT NULL DEFAULT '0',
  "item_consensus" smallint NOT NULL DEFAULT '0',
  "item_thread_top" smallint NOT NULL DEFAULT '0',
  "item_notshown" smallint NOT NULL DEFAULT '0',
  "item_nsfw" smallint NOT NULL DEFAULT '0',
  "item_relay" smallint NOT NULL DEFAULT '0',
  "item_mentionsme" smallint NOT NULL DEFAULT '0',
  "item_nocomment" smallint NOT NULL DEFAULT '0',
  "item_obscured" smallint NOT NULL DEFAULT '0',
  "item_verified" smallint NOT NULL DEFAULT '0',
  "item_retained" smallint NOT NULL DEFAULT '0',
  "item_rss" smallint NOT NULL DEFAULT '0',
  "item_deleted" smallint NOT NULL DEFAULT '0',
  "item_type" int NOT NULL DEFAULT '0',
  "item_hidden" smallint NOT NULL DEFAULT '0',
  "item_unpublished" smallint NOT NULL DEFAULT '0',
  "item_delayed" smallint NOT NULL DEFAULT '0',
  "item_pending_remove" smallint NOT NULL DEFAULT '0',
  "item_blocked" smallint NOT NULL DEFAULT '0',
  "item_search_vector" tsvector,
  PRIMARY KEY ("id")
);
create index "item_uuid" on item ("uuid");
create index "item_parent" on item ("parent");
create index "item_created" on item ("created");
create index "item_edited" on item ("edited");
create index "item_received" on item ("received");
create index "item_uid_commented" on item ("uid","commented");
create index "item_uid_created" on item ("uid","created");
create index "item_uid_unseen" on item ("uid","item_unseen");
create index "item_changed" on item ("changed");
create index "item_comments_closed" on item ("comments_closed");
create index "item_owner_xchan" on item ("owner_xchan");
create index "item_author_xchan" on item ("author_xchan");
create index "item_resource_id" on item ("resource_id");
create index "item_resource_type" on item ("resource_type");
create index "item_commented" on item ("commented");
create index "item_verb" on item ("verb");
create index "item_obj_type" on item ("obj_type");
create index "item_expires" on item ("expires");
create index "item_revision" on item ("revision");
create index "item_mimetype" on item ("mimetype");
create index "item_mid" on item ("mid");
create index "item_parent_mid" on item ("parent_mid");
create index "item_uid_mid" on item ("uid","mid");
create index "item_public_policy" on item ("public_policy");
create index "item_comment_policy" on item ("comment_policy");
create index "item_layout_mid" on item ("layout_mid");
create index "item_wall" on item ("item_wall");

create index "item_origin" on item ("item_origin");
create index "item_uplink" on item ("item_uplink");
create index "item_consensus" on item ("item_consensus");
create index "item_nsfw" on item ("item_nsfw");
create index "item_mentionsme" on item ("item_mentionsme");
create index "item_nocomment" on item ("item_nocomment");
create index "item_obscured" on item ("item_obscured");
create index "item_rss" on item ("item_rss");
create index "item_thr_parent" on item ("thr_parent");

create index "item_uid_item_type" on item ("uid", "item_type");
create index "item_uid_item_thread_top" on item ("uid", "item_thread_top");
create index "item_uid_item_blocked" on item ("uid", "item_blocked");
create index "item_uid_item_wall" on item ("uid", "item_wall");
create index "item_uid_item_starred" on item ("uid", "item_starred");
create index "item_uid_item_retained" on item ("uid", "item_retained");
create index "item_uid_item_private" on item ("uid", "item_private");
create index "item_uid_resource_type" on item ("uid", "resource_type");
create index "item_item_deleted_item_pending_remove_changed" on item ("item_deleted", "item_pending_remove", "changed");
create index "item_item_pending_remove_changed" on item ("item_pending_remove", "changed");

-- fulltext indexes
create index "item_search_idx" on  item USING gist("item_search_vector");
create index "item_allow_cid" on item ("allow_cid");
create index "item_allow_gid" on item ("allow_gid");
create index "item_deny_cid" on item ("deny_cid");
create index "item_deny_gid" on item ("deny_gid");

CREATE TABLE "item_id" (
  "id" serial  NOT NULL,
  "iid" bigint NOT NULL,
  "uid" bigint NOT NULL,
  "sid" text NOT NULL,
  "service" text NOT NULL,
  PRIMARY KEY ("id")

);
create index "itemid_uid" on item_id ("uid");
create index "itemid_sid" on item_id ("sid");
create index "itemid_service" on item_id ("service");
create index "itemid_iid" on item_id ("iid");
CREATE TABLE "likes" (
  "id" serial  NOT NULL,
  "channel_id" bigint  NOT NULL DEFAULT '0',
  "liker" varchar(128) NOT NULL DEFAULT '',
  "likee" varchar(128) NOT NULL DEFAULT '',
  "iid" bigint  NOT NULL DEFAULT '0',
  "i_mid" varchar(255) NOT NULL DEFAULT '',
  "verb" text NOT NULL DEFAULT '',
  "target_type" text NOT NULL DEFAULT '',
  "target_id" varchar(128) NOT NULL DEFAULT '',
  "target" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "likes_channel_id" on likes ("channel_id");
create index "likes_liker" on likes ("liker");
create index "likes_likee" on likes ("likee");
create index "likes_iid" on likes ("iid");
create index "likes_i_mid" on likes ("i_mid");
create index "likes_verb" on likes ("verb");
create index "likes_target_type" on likes ("target_type");
create index "likes_target_id" on likes ("target_id");
CREATE TABLE listeners (
  id serial NOT NULL,
  target_id text NOT NULL,
  portable_id text NOT NULL,
  ltype smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);
create index "target_id_idx" on listeners ("target_id");
create index "portable_id_idx" on listeners ("portable_id");
create index "ltype_idx" on listeners ("ltype");

CREATE TABLE "menu" (
  "menu_id" serial  NOT NULL,
  "menu_channel_id" bigint  NOT NULL DEFAULT '0',
  "menu_name" text NOT NULL DEFAULT '',
  "menu_desc" text NOT NULL DEFAULT '',
  "menu_flags" bigint NOT NULL DEFAULT '0',
  "menu_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "menu_edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("menu_id")
);
create index "menu_channel_id" on menu ("menu_channel_id");
create index "menu_name" on menu ("menu_name");
create index "menu_flags" on menu ("menu_flags");
create index "menu_created" on menu ("menu_created");
create index "menu_edited" on menu ("menu_edited");
CREATE TABLE "menu_item" (
  "mitem_id" serial  NOT NULL,
  "mitem_link" text NOT NULL DEFAULT '',
  "mitem_desc" text NOT NULL DEFAULT '',
  "mitem_flags" bigint NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  "mitem_channel_id" bigint  NOT NULL,
  "mitem_menu_id" bigint  NOT NULL DEFAULT '0',
  "mitem_order" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("mitem_id")

);
create index "mitem_channel_id" on menu_item ("mitem_channel_id");
create index "mitem_menu_id" on menu_item ("mitem_menu_id");
create index "mitem_flags" on menu_item ("mitem_flags");
CREATE TABLE "notify" (
  "id" serial NOT NULL,
  "hash" varchar(64) NOT NULL,
  "xname" text NOT NULL,
  "url" text NOT NULL,
  "photo" text NOT NULL,
  "created" timestamp NOT NULL,
  "msg" text NOT NULL DEFAULT '',
  "aid" bigint NOT NULL,
  "uid" bigint NOT NULL,
  "link" text NOT NULL,
  "parent" text NOT NULL DEFAULT '',
  "seen" numeric(1) NOT NULL DEFAULT '0',
  "ntype" bigint NOT NULL,
  "verb" text NOT NULL,
  "otype" varchar(16) NOT NULL,
  PRIMARY KEY ("id")
);
create index "notify_ntype" on notify ("ntype");
create index "notify_seen" on notify ("seen");
create index "notify_uid" on notify ("uid");
create index "notify_created" on notify ("created");
create index "notify_hash" on notify ("hash");
create index "notify_parent" on notify ("parent");
create index "notify_link" on notify ("link");
create index "notify_otype" on notify ("otype");
create index "notify_aid" on notify ("aid");
CREATE TABLE "obj" (
  "obj_id" serial  NOT NULL,
  "obj_page" varchar(64) NOT NULL DEFAULT '',
  "obj_verb" text NOT NULL DEFAULT '',
  "obj_type" bigint  NOT NULL DEFAULT 0,
  "obj_obj" text NOT NULL DEFAULT '',
  "obj_channel" bigint  NOT NULL DEFAULT 0,
  "obj_term" varchar(255) NOT NULL DEFAULT '',
  "obj_url" varchar(255) NOT NULL DEFAULT '',
  "obj_imgurl" varchar(255) NOT NULL DEFAULT '',
  "obj_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "obj_edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "obj_quantity" bigint NOT NULL DEFAULT 0,
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("obj_id")

);
create index "obj_verb" on obj ("obj_verb");
create index "obj_page" on obj ("obj_page");
create index "obj_type" on obj ("obj_type");
create index "obj_channel" on obj ("obj_channel");
create index "obj_obj" on obj ("obj_obj");
create index "obj_term" on obj ("obj_term");
create index "obj_url" on obj ("obj_url");
create index "obj_imgurl" on obj ("obj_imgurl");
create index "obj_created" on obj ("obj_created");
create index "obj_edited" on obj ("obj_edited");
create index "obj_quantity" on obj ("obj_quantity");

CREATE TABLE "outq" (
  "outq_hash" text NOT NULL,
  "outq_account" bigint  NOT NULL DEFAULT '0',
  "outq_channel" bigint  NOT NULL DEFAULT '0',
  "outq_driver" varchar(32) NOT NULL DEFAULT '',
  "outq_posturl" text NOT NULL DEFAULT '',
  "outq_async" numeric(1) NOT NULL DEFAULT '0',
  "outq_delivered" numeric(1) NOT NULL DEFAULT '0',
  "outq_created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "outq_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "outq_scheduled" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "outq_notify" text NOT NULL,
  "outq_msg" text NOT NULL,
  "outq_priority" smallint NOT NULL DEFAULT '0',
  PRIMARY KEY ("outq_hash")
);
create index "outq_account" on outq ("outq_account");
create index "outq_channel" on outq ("outq_channel");
create index "outq_hub" on outq ("outq_posturl");
create index "outq_created" on outq ("outq_created");
create index "outq_updated" on outq ("outq_updated");
create index "outq_scheduled" on outq ("outq_scheduled");
create index "outq_async" on outq ("outq_async");
create index "outq_delivered" on outq ("outq_delivered");
create index "outq_priority" on outq ("outq_priority");

CREATE TABLE "pchan" (
  "pchan_id" serial NOT NULL,
  "pchan_guid" text NOT NULL,
  "pchan_hash" text NOT NULL,
  "pchan_pubkey" text NOT NULL,
  "pchan_prvkey" text NOT NULL,
  PRIMARY KEY ("pchan_id")
);
create index "pchan_guid" on pchan ("pchan_guid");
create index "pchan_hash" on pchan ("pchan_hash");

CREATE TABLE "pconfig" (
  "id" serial NOT NULL,
  "uid" bigint NOT NULL DEFAULT '0',
  "cat" text  NOT NULL,
  "k" text  NOT NULL,
  "v" text NOT NULL,
  "updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("id"),
  UNIQUE ("uid","cat","k")
);
create index "pconfig_updated_idx" on pconfig ("updated");

CREATE TABLE "photo" (
  "id" serial  NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL,
  "xchan" text NOT NULL DEFAULT '',
  "resource_id" text NOT NULL,
  "created" timestamp NOT NULL,
  "edited" timestamp NOT NULL,
  "expires" timestamp NOT NULL,
  "title" text NOT NULL,
  "description" text NOT NULL,
  "album" text NOT NULL,
  "filename" text NOT NULL,
  "mimetype" varchar(128) NOT NULL DEFAULT 'image/jpeg',
  "height" numeric(6) NOT NULL,
  "width" numeric(6) NOT NULL,
  "filesize" bigint  NOT NULL DEFAULT '0',
  "content" bytea NOT NULL,
  "imgscale" numeric(3) NOT NULL DEFAULT '0',
  "profile" numeric(1) NOT NULL DEFAULT '0',
  "photo_usage" smallint NOT NULL DEFAULT '0',
  "is_nsfw" smallint NOT NULL DEFAULT '0',
  "os_storage" smallint NOT NULL DEFAULT '0',
  "os_path" text NOT NULL,
  "display_path" text NOT NULL,
  "photo_flags" bigint  NOT NULL DEFAULT '0',
  "allow_cid" text NOT NULL,
  "allow_gid" text NOT NULL,
  "deny_cid" text NOT NULL,
  "deny_gid" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "photo_uid" on photo ("uid");
create index "photo_album" on photo ("album");
create index "photo_imgscale" on photo ("imgscale");
create index "photo_profile" on photo ("profile");
create index "photo_flags" on photo ("photo_flags");
create index "photo_mimetype" on photo ("mimetype");
create index "photo_aid" on photo ("aid");
create index "photo_xchan" on photo ("xchan");
create index "photo_filesize" on photo ("filesize");
create index "photo_resource_id" on photo ("resource_id");
create index "photo_expires_idx" on photo ("expires");
create index "photo_usage" on photo ("photo_usage");
create index "photo_is_nsfw" on photo ("is_nsfw");
create index "photo_os_storage" on photo ("os_storage");

CREATE TABLE "poll" (
  "poll_id" serial  NOT NULL,
  "poll_guid" text NOT NULL,
  "poll_channel" bigint  NOT NULL DEFAULT '0',
  "poll_author" text NOT NULL,
  "poll_desc" text NOT NULL,
  "poll_flags" bigint NOT NULL DEFAULT '0',
  "poll_votes" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("poll_id")

);
create index "poll_guid" on poll ("poll_guid");
create index "poll_channel" on poll ("poll_channel");
create index "poll_author" on poll ("poll_author");
create index "poll_flags" on poll ("poll_flags");
create index "poll_votes" on poll ("poll_votes");
CREATE TABLE "poll_elm" (
  "pelm_id" serial  NOT NULL,
  "pelm_guid" text NOT NULL,
  "pelm_poll" bigint  NOT NULL DEFAULT '0',
  "pelm_desc" text NOT NULL,
  "pelm_flags" bigint NOT NULL DEFAULT '0',
  "pelm_result" float NOT NULL DEFAULT '0',
  "pelm_order" numeric(6) NOT NULL DEFAULT '0',
  PRIMARY KEY ("pelm_id")
);
create index "pelm_guid" on poll_elm ("pelm_guid");
create index "pelm_poll" on poll_elm ("pelm_poll");
create index "pelm_result" on poll_elm ("pelm_result");
create index "pelm_order" on poll_elm ("pelm_order");

CREATE TABLE "profdef" (
  "id" serial  NOT NULL,
  "field_name" text NOT NULL DEFAULT '',
  "field_type" varchar(16) NOT NULL DEFAULT '',
  "field_desc" text NOT NULL DEFAULT '',
  "field_help" text NOT NULL DEFAULT '',
  "field_inputs" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "profdef_field_name" on profdef ("field_name");
CREATE TABLE "profext" (
  "id" serial  NOT NULL,
  "channel_id" bigint  NOT NULL DEFAULT '0',
  "hash" text NOT NULL DEFAULT '',
  "k" text NOT NULL DEFAULT '',
  "v" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "profext_channel_id" on profext ("channel_id");
create index "profext_hash" on profext ("hash");
create index "profext_k" on profext ("k");

CREATE TABLE "profile" (
  "id" serial NOT NULL,
  "profile_guid" varchar(64) NOT NULL DEFAULT '',
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint NOT NULL,
  "profile_name" text NOT NULL,
  "is_default" numeric(1) NOT NULL DEFAULT '0',
  "hide_friends" numeric(1) NOT NULL DEFAULT '0',
  "fullname" text NOT NULL,
  "pdesc" text NOT NULL DEFAULT '',
  "chandesc" text NOT NULL DEFAULT '',
  "dob" varchar(32) NOT NULL DEFAULT '',
  "dob_tz" text NOT NULL DEFAULT 'UTC',
  "address" text NOT NULL DEFAULT '',
  "locality" text NOT NULL DEFAULT '',
  "region" text NOT NULL DEFAULT '',
  "postal_code" varchar(32) NOT NULL DEFAULT '',
  "country_name" text NOT NULL DEFAULT '',
  "hometown" text NOT NULL DEFAULT '',
  "gender" varchar(32) NOT NULL DEFAULT '',
  "marital" text NOT NULL DEFAULT '',
  "partner" text NOT NULL DEFAULT '',
  "howlong" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "sexual" text NOT NULL DEFAULT '',
  "politic" text NOT NULL DEFAULT '',
  "religion" text NOT NULL DEFAULT '',
  "keywords" text NOT NULL DEFAULT '',
  "likes" text NOT NULL DEFAULT '',
  "dislikes" text NOT NULL DEFAULT '',
  "about" text NOT NULL DEFAULT '',
  "summary" text NOT NULL DEFAULT '',
  "music" text NOT NULL DEFAULT '',
  "book" text NOT NULL DEFAULT '',
  "tv" text NOT NULL DEFAULT '',
  "film" text NOT NULL DEFAULT '',
  "interest" text NOT NULL DEFAULT '',
  "romance" text NOT NULL DEFAULT '',
  "employment" text NOT NULL DEFAULT '',
  "education" text NOT NULL DEFAULT '',
  "contact" text NOT NULL DEFAULT '',
  "channels" text NOT NULL DEFAULT '',
  "homepage" text NOT NULL DEFAULT '',
  "photo" text NOT NULL,
  "thumb" text NOT NULL,
  "publish" numeric(1) NOT NULL DEFAULT '0',
  "profile_vcard" text NOT NULL DEFAULT '',
  PRIMARY KEY ("id"),
  UNIQUE ("profile_guid","uid")

);
create index "profile_uid" on profile ("uid");
create index "profile_locality" on profile ("locality");
create index "profile_hometown" on profile ("hometown");
create index "profile_gender" on profile ("gender");
create index "profile_marital" on profile ("marital");
create index "profile_sexual" on profile ("sexual");
create index "profile_publish" on profile ("publish");
create index "profile_aid" on profile ("aid");
create index "profile_is_default" on profile ("is_default");
create index "profile_hide_friends" on profile ("hide_friends");
create index "profile_postal_code" on profile ("postal_code");
create index "profile_country_name" on profile ("country_name");
create index "profile_guid" on profile ("profile_guid");
CREATE TABLE "profile_check" (
  "id" serial  NOT NULL,
  "uid" bigint  NOT NULL,
  "cid" bigint  NOT NULL DEFAULT '0',
  "dfrn_id" text NOT NULL,
  "sec" text NOT NULL,
  "expire" bigint NOT NULL,
  PRIMARY KEY ("id")
);
create index "pc_uid" on profile_check ("uid");
create index "pc_cid" on profile_check ("cid");
create index "pc_dfrn_id" on profile_check ("dfrn_id");
create index "pc_sec" on profile_check ("sec");
create index "pc_expire" on profile_check ("expire");

CREATE TABLE "register" (
  "reg_id"     serial  NOT NULL,
  "reg_vital"  int     DEFAULT 1 NOT NULL,
  "reg_flags" bigint  DEFAULT 0 NOT NULL,
  "reg_didx"   char(1) DEFAULT '' NOT NULL,
  "reg_did2"   text    DEFAULT '' NOT NULL,
  "reg_hash"   text    DEFAULT '' NOT NULL,
  "reg_email"  text    DEFAULT '' NOT NULL,
  "reg_created" timestamp  NOT NULL DEFAULT '0001-01-01 00:00:00',
  "reg_startup" timestamp  NOT NULL DEFAULT '0001-01-01 00:00:00',
  "reg_expires" timestamp  NOT NULL DEFAULT '0001-01-01 00:00:00',
  "reg_byc"    bigint  DEFAULT 0 NOT NULL,
  "reg_uid"    bigint  DEFAULT 0 NOT NULL,
  "reg_atip"   text    DEFAULT '' NOT NULL,
  "reg_pass"   text    DEFAULT '' NOT NULL,
  "reg_lang"   varchar(16) DEFAULT '' NOT NULL,
  "reg_stuff"  text    NOT NULL,
  PRIMARY KEY ("reg_id")
);
create index "ix_reg_vital" on register ("reg_vital");
create index "ix_reg_flags" on register ("reg_flags");
create index "ix_reg_didx" on register ("reg_didx");
create index "ix_reg_did2" on register ("reg_did2");
create index "ix_reg_hash" on register ("reg_hash");
create index "ix_reg_email" on register ("reg_email");
create index "ix_reg_created" on register ("reg_created");
create index "ix_reg_startup" on register ("reg_startup");
create index "ix_reg_expires" on register ("reg_expires");
create index "ix_reg_byc" on register ("reg_byc");
create index "ix_reg_uid" on register ("reg_uid");
create index "ix_reg_atip" on register ("reg_atip");

CREATE TABLE "session" (
  "id" serial,
  "sid" text NOT NULL,
  "sess_data" text NOT NULL,
  "expire" numeric(20)  NOT NULL,
  PRIMARY KEY ("id")
);
create index "session_sid" on session ("sid");
create index "session_expire" on session ("expire");
CREATE TABLE "shares" (
  "share_id" serial  NOT NULL,
  "share_type" bigint NOT NULL DEFAULT '0',
  "share_target" bigint  NOT NULL DEFAULT '0',
  "share_xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("share_id")
);
create index "share_type" on shares ("share_type");
create index "share_target" on shares ("share_target");
create index "share_xchan" on shares ("share_xchan");

CREATE TABLE "sign" (
  "id" serial  NOT NULL,
  "iid" bigint  NOT NULL DEFAULT '0',
  "retract_iid" bigint  NOT NULL DEFAULT '0',
  "signed_text" text NOT NULL,
  "signature" text NOT NULL,
  "signer" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "sign_iid" on "sign" ("iid");
create index "sign_retract_iid" on "sign" ("retract_iid");

CREATE TABLE "site" (
  "site_url" text NOT NULL,
  "site_access" bigint NOT NULL DEFAULT '0',
  "site_flags" bigint NOT NULL DEFAULT '0',
  "site_update" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "site_pull" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "site_sync" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "site_directory" text NOT NULL DEFAULT '',
  "site_register" bigint NOT NULL DEFAULT '0',
  "site_sellpage" text NOT NULL DEFAULT '',
  "site_location" text NOT NULL DEFAULT '',
  "site_realm" text NOT NULL DEFAULT '',
  "site_valid" smallint NOT NULL DEFAULT '0',
  "site_dead" smallint NOT NULL DEFAULT '0',
  "site_type" smallint NOT NULL DEFAULT '0',
  "site_project" text NOT NULL DEFAULT '',
  "site_version" text NOT NULL DEFAULT '',
  "site_crypto" text NOT NULL DEFAULT '',
  PRIMARY KEY ("site_url")
);
create index "site_flags" on site ("site_flags");
create index "site_update" on site  ("site_update");
create index "site_directory" on site ("site_directory");
create index "site_register" on site ("site_register");
create index "site_access" on site ("site_access");
create index "site_sellpage" on site ("site_sellpage");
create index "site_realm" on site ("site_realm");
create index "site_valid" on site ("site_valid");
create index "site_dead" on site ("site_dead");
create index "site_type" on site ("site_type");
create index "site_project" on site ("site_project");

CREATE TABLE "source" (
  "src_id" serial  NOT NULL,
  "src_channel_id" bigint  NOT NULL DEFAULT '0',
  "src_channel_xchan" text NOT NULL DEFAULT '',
  "src_xchan" text NOT NULL DEFAULT '',
  "src_patt" text NOT NULL DEFAULT '',
  "src_tag" text NOT NULL DEFAULT '',
  PRIMARY KEY ("src_id")
);
create index "src_channel_id" on "source" ("src_channel_id");
create index "src_channel_xchan" on "source"  ("src_channel_xchan");
create index "src_xchan" on "source" ("src_xchan");
CREATE TABLE "sys_perms" (
  "id" serial  NOT NULL,
  "cat" text NOT NULL,
  "k" text NOT NULL,
  "v" text NOT NULL,
  "public_perm" numeric(1)  NOT NULL,
  PRIMARY KEY ("id")
);
CREATE TABLE "term" (
  "tid" serial  NOT NULL,
  "aid" bigint  NOT NULL DEFAULT '0',
  "uid" bigint  NOT NULL DEFAULT '0',
  "oid" bigint  NOT NULL,
  "otype" numeric(3)  NOT NULL,
  "ttype" numeric(3)  NOT NULL,
  "term" text NOT NULL,
  "url" text NOT NULL,
  "imgurl" text NOT NULL DEFAULT '',
  "term_hash" text NOT NULL DEFAULT '',
  "parent_hash" text NOT NULL DEFAULT '',
  PRIMARY KEY ("tid")
);
create index "term_oid" on term ("oid");
create index "term_otype" on term ("otype");
create index "term_ttype" on term ("ttype");
create index "term_term" on term ("term");
create index "term_uid" on term ("uid");
create index "term_aid" on term ("aid");
create index "term_imgurl" on term ("imgurl");
create index "term_hash" on term ("term_hash");
create index "term_parent_hash" on term ("parent_hash");
CREATE TABLE "tokens" (
  "id" varchar(40) NOT NULL,
  "secret" text NOT NULL,
  "client_id" varchar(20) NOT NULL,
  "expires" numeric(20)  NOT NULL,
  "auth_scope" varchar(512) NOT NULL,
  "uid" bigint NOT NULL,
  PRIMARY KEY ("id")
);
create index "tokens_client_id" on tokens ("client_id");
create index "tokens_expires" on tokens ("expires");
create index "tokens_uid" on tokens ("uid");

CREATE TABLE "updates" (
  "ud_id" serial  NOT NULL,
  "ud_hash" varchar(128) NOT NULL,
  "ud_date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "ud_last" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "ud_flags" bigint NOT NULL DEFAULT '0',
  "ud_addr" text NOT NULL DEFAULT '',
  "ud_update" smallint NOT NULL DEFAULT '0',
  "ud_host" text NOT NULL DEFAULT '',
  PRIMARY KEY ("ud_id")
);
create index "ud_date" on updates ("ud_date");
create index "ud_hash" on updates ("ud_hash");
create index "ud_flags" on updates ("ud_flags");
create index "ud_addr" on updates ("ud_addr");
create index "ud_last" on updates ("ud_last");
create index "ud_update" on updates ("ud_update");

CREATE TABLE "verify" (
  "id" serial  NOT NULL,
  "channel" bigint  NOT NULL DEFAULT '0',
  "vtype" varchar(32) NOT NULL DEFAULT '',
  "token" text NOT NULL DEFAULT '',
  "meta" text NOT NULL DEFAULT '',
  "created" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("id")
);
create index "verify_channel" on verify ("channel");
create index "verify_vtype" on verify ("vtype");
create index "verify_token" on verify ("token");
create index "verify_meta" on verify ("meta");
create index "verify_created" on verify ("created");
CREATE TABLE "vote" (
  "vote_id" serial  NOT NULL,
  "vote_guid" text NOT NULL,
  "vote_poll" bigint NOT NULL DEFAULT '0',
  "vote_element" bigint NOT NULL DEFAULT '0',
  "vote_result" text NOT NULL,
  "vote_xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("vote_id"),
  UNIQUE ("vote_poll","vote_element","vote_xchan")
);
create index "vote_guid" on vote ("vote_guid");
create index "vote_poll" on vote ("vote_poll");
create index "vote_element" on vote ("vote_element");
CREATE TABLE "xchan" (
  "xchan_hash" text NOT NULL,
  "xchan_guid" text NOT NULL DEFAULT '',
  "xchan_guid_sig" text NOT NULL DEFAULT '',
  "xchan_pubkey" text NOT NULL DEFAULT '',
  "xchan_photo_mimetype" text NOT NULL DEFAULT 'image/jpeg',
  "xchan_photo_l" text NOT NULL DEFAULT '',
  "xchan_photo_m" text NOT NULL DEFAULT '',
  "xchan_photo_s" text NOT NULL DEFAULT '',
  "xchan_addr" text NOT NULL DEFAULT '',
  "xchan_url" text NOT NULL DEFAULT '',
  "xchan_connurl" text NOT NULL DEFAULT '',
  "xchan_follow" text NOT NULL DEFAULT '',
  "xchan_connpage" text NOT NULL DEFAULT '',
  "xchan_name" text NOT NULL DEFAULT '',
  "xchan_network" text NOT NULL DEFAULT '',
  "xchan_instance_url" text NOT NULL DEFAULT '',
  "xchan_flags" bigint  NOT NULL DEFAULT '0',
  "xchan_photo_date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "xchan_name_date" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "xchan_hidden" smallint NOT NULL DEFAULT '0',
  "xchan_orphan" smallint NOT NULL DEFAULT '0',
  "xchan_censored" smallint NOT NULL DEFAULT '0',
  "xchan_selfcensored" smallint NOT NULL DEFAULT '0',
  "xchan_system" smallint NOT NULL DEFAULT '0',
  "xchan_pubforum" smallint NOT NULL DEFAULT '0',
  "xchan_deleted" smallint NOT NULL DEFAULT '0',
  PRIMARY KEY ("xchan_hash")
);
create index "xchan_guid" on xchan ("xchan_guid");
create index "xchan_addr" on xchan ("xchan_addr");
create index "xchan_name" on xchan ("xchan_name");
create index "xchan_network" on xchan ("xchan_network");
create index "xchan_url" on xchan ("xchan_url");
create index "xchan_flags" on xchan ("xchan_flags");
create index "xchan_connurl" on xchan ("xchan_connurl");
create index "xchan_instance_url" on xchan ("xchan_instance_url");
create index "xchan_follow" on xchan ("xchan_follow");
create index "xchan_hidden" on xchan ("xchan_hidden");
create index "xchan_orphan" on xchan ("xchan_orphan");
create index "xchan_censored" on xchan ("xchan_censored");
create index "xchan_selfcensored" on xchan ("xchan_selfcensored");
create index "xchan_system" on xchan ("xchan_system");
create index "xchan_pubforum" on xchan ("xchan_pubforum");
create index "xchan_deleted" on xchan ("xchan_deleted");
create index "xchan_photo_m" on xchan ("xchan_photo_m");

CREATE TABLE "xchat" (
  "xchat_id" serial  NOT NULL,
  "xchat_url" text NOT NULL DEFAULT '',
  "xchat_desc" text NOT NULL DEFAULT '',
  "xchat_xchan" text NOT NULL DEFAULT '',
  "xchat_edited" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  PRIMARY KEY ("xchat_id")
);
create index "xchat_url" on xchat ("xchat_url");
create index "xchat_desc" on xchat ("xchat_desc");
create index "xchat_xchan" on xchat ("xchat_xchan");
create index "xchat_edited" on xchat ("xchat_edited");
CREATE TABLE "xconfig" (
  "id" serial  NOT NULL,
  "xchan" text NOT NULL,
  "cat" text NOT NULL,
  "k" text NOT NULL,
  "v" text NOT NULL,
  PRIMARY KEY ("id")
);
create index "xconfig_xchan" on xconfig ("xchan");
create index "xconfig_cat" on xconfig ("cat");
create index "xconfig_k" on xconfig ("k");
CREATE TABLE "xign" (
  "id" serial  NOT NULL,
  "uid" bigint NOT NULL DEFAULT '0',
  "xchan" text NOT NULL DEFAULT '',
  PRIMARY KEY ("id")
);
create index "xign_uid" on xign ("uid");
create index "xign_xchan" on xign ("xchan");
CREATE TABLE "xlink" (
  "xlink_id" serial  NOT NULL,
  "xlink_xchan" text NOT NULL DEFAULT '',
  "xlink_link" text NOT NULL DEFAULT '',
  "xlink_rating" bigint NOT NULL DEFAULT '0',
  "xlink_rating_text" TEXT NOT NULL DEFAULT '',
  "xlink_updated" timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
  "xlink_static" numeric(1) NOT NULL DEFAULT '0',
  "xlink_sig" text NOT NULL DEFAULT '',
  PRIMARY KEY ("xlink_id")
);
create index "xlink_xchan" on xlink ("xlink_xchan");
create index "xlink_link" on xlink ("xlink_link");
create index "xlink_updated" on xlink ("xlink_updated");
create index "xlink_rating" on xlink ("xlink_rating");
create index "xlink_static" on xlink ("xlink_static");
CREATE TABLE "xperm" (
  "xp_id" serial NOT NULL,
  "xp_client" varchar( 20 ) NOT NULL DEFAULT '',
  "xp_channel" bigint NOT NULL DEFAULT '0',
  "xp_perm" varchar( 64 ) NOT NULL DEFAULT '',
  PRIMARY KEY ("xp_id")
);
create index "xp_client" on xperm ("xp_client");
create index "xp_channel" on xperm ("xp_channel");
create index "xp_perm" on xperm ("xp_perm");
CREATE TABLE "xprof" (
  "xprof_hash" text NOT NULL,
  "xprof_age" numeric(3)  NOT NULL DEFAULT '0',
  "xprof_desc" text NOT NULL DEFAULT '',
  "xprof_dob" varchar(12) NOT NULL DEFAULT '',
  "xprof_gender" text NOT NULL DEFAULT '',
  "xprof_marital" text NOT NULL DEFAULT '',
  "xprof_sexual" text NOT NULL DEFAULT '',
  "xprof_locale" text NOT NULL DEFAULT '',
  "xprof_region" text NOT NULL DEFAULT '',
  "xprof_postcode" varchar(32) NOT NULL DEFAULT '',
  "xprof_country" text NOT NULL DEFAULT '',
  "xprof_keywords" text NOT NULL,
  "xprof_about" text NOT NULL,
  "xprof_homepage" text NOT NULL DEFAULT '',
  "xprof_hometown" text NOT NULL DEFAULT '',
  PRIMARY KEY ("xprof_hash")
);
create index "xprof_desc" on xprof ("xprof_desc");
create index "xprof_dob" on xprof ("xprof_dob");
create index "xprof_gender" on xprof ("xprof_gender");
create index "xprof_marital" on xprof ("xprof_marital");
create index "xprof_sexual" on xprof ("xprof_sexual");
create index "xprof_locale" on xprof ("xprof_locale");
create index "xprof_region" on xprof ("xprof_region");
create index "xprof_postcode" on xprof ("xprof_postcode");
create index "xprof_country" on xprof ("xprof_country");
create index "xprof_age" on xprof ("xprof_age");
create index "xprof_hometown" on xprof ("xprof_hometown");
CREATE TABLE "xtag" (
  "xtag_id" serial  NOT NULL,
  "xtag_hash" text NOT NULL,
  "xtag_term" text NOT NULL DEFAULT '',
  "xtag_flags" bigint NOT NULL DEFAULT '0',
  PRIMARY KEY ("xtag_id")
);
create index "xtag_term" on xtag ("xtag_term");
create index "xtag_hash" on xtag ("xtag_hash");
create index "xtag_flags" on xtag ("xtag_flags");

CREATE TABLE addressbooks (
    id SERIAL NOT NULL,
    principaluri VARCHAR(255),
    displayname VARCHAR(255),
    uri VARCHAR(200),
    description TEXT,
    synctoken INTEGER NOT NULL DEFAULT 1
);

ALTER TABLE ONLY addressbooks
    ADD CONSTRAINT addressbooks_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX addressbooks_ukey
    ON addressbooks USING btree (principaluri, uri);

CREATE TABLE cards (
    id SERIAL NOT NULL,
    addressbookid INTEGER NOT NULL,
    carddata BYTEA,
    uri VARCHAR(200),
    lastmodified INTEGER,
    etag VARCHAR(32),
    size INTEGER NOT NULL
);

ALTER TABLE ONLY cards
    ADD CONSTRAINT cards_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX cards_ukey
    ON cards USING btree (addressbookid, uri);

CREATE TABLE addressbookchanges (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    synctoken INTEGER NOT NULL,
    addressbookid INTEGER NOT NULL,
    operation SMALLINT NOT NULL
);

ALTER TABLE ONLY addressbookchanges
    ADD CONSTRAINT addressbookchanges_pkey PRIMARY KEY (id);

CREATE INDEX addressbookchanges_addressbookid_synctoken_ix
    ON addressbookchanges USING btree (addressbookid, synctoken);

CREATE TABLE calendarobjects (
    id SERIAL NOT NULL,
    calendardata BYTEA,
    uri VARCHAR(200),
    calendarid INTEGER NOT NULL,
    lastmodified INTEGER,
    etag VARCHAR(32),
    size INTEGER NOT NULL,
    componenttype VARCHAR(8),
    firstoccurence INTEGER,
    lastoccurence INTEGER,
    uid VARCHAR(200)
);

ALTER TABLE ONLY calendarobjects
    ADD CONSTRAINT calendarobjects_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX calendarobjects_ukey
    ON calendarobjects USING btree (calendarid, uri);


CREATE TABLE calendars (
    id SERIAL NOT NULL,
    synctoken INTEGER NOT NULL DEFAULT 1,
    components VARCHAR(21)
);

ALTER TABLE ONLY calendars
    ADD CONSTRAINT calendars_pkey PRIMARY KEY (id);


CREATE TABLE calendarinstances (
    id SERIAL NOT NULL,
    calendarid INTEGER NOT NULL,
    principaluri VARCHAR(100),
    access SMALLINT NOT NULL DEFAULT '1', -- '1 = owner, 2 = read, 3 = readwrite'
    displayname VARCHAR(100),
    uri VARCHAR(200),
    description TEXT,
    calendarorder INTEGER NOT NULL DEFAULT 0,
    calendarcolor VARCHAR(10),
    timezone TEXT,
    transparent SMALLINT NOT NULL DEFAULT '0',
    share_href VARCHAR(100),
    share_displayname VARCHAR(100),
    share_invitestatus SMALLINT NOT NULL DEFAULT '2' --  '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid'
);

ALTER TABLE ONLY calendarinstances
    ADD CONSTRAINT calendarinstances_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX calendarinstances_principaluri_uri
    ON calendarinstances USING btree (principaluri, uri);


CREATE UNIQUE INDEX calendarinstances_principaluri_calendarid
    ON calendarinstances USING btree (principaluri, calendarid);

CREATE UNIQUE INDEX calendarinstances_principaluri_share_href
    ON calendarinstances USING btree (principaluri, share_href);

CREATE TABLE calendarsubscriptions (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    principaluri VARCHAR(100) NOT NULL,
    source TEXT,
    displayname VARCHAR(100),
    refreshrate VARCHAR(10),
    calendarorder INTEGER NOT NULL DEFAULT 0,
    calendarcolor VARCHAR(10),
    striptodos SMALLINT NULL,
    stripalarms SMALLINT NULL,
    stripattachments SMALLINT NULL,
    lastmodified INTEGER
);

ALTER TABLE ONLY calendarsubscriptions
    ADD CONSTRAINT calendarsubscriptions_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX calendarsubscriptions_ukey
    ON calendarsubscriptions USING btree (principaluri, uri);

CREATE TABLE calendarchanges (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    synctoken INTEGER NOT NULL,
    calendarid INTEGER NOT NULL,
    operation SMALLINT NOT NULL DEFAULT 0
);

ALTER TABLE ONLY calendarchanges
    ADD CONSTRAINT calendarchanges_pkey PRIMARY KEY (id);

CREATE INDEX calendarchanges_calendarid_synctoken_ix
    ON calendarchanges USING btree (calendarid, synctoken);

CREATE TABLE schedulingobjects (
    id SERIAL NOT NULL,
    principaluri VARCHAR(255),
    calendardata BYTEA,
    uri VARCHAR(200),
    lastmodified INTEGER,
    etag VARCHAR(32),
    size INTEGER NOT NULL
);

CREATE TABLE locks (
    id SERIAL NOT NULL,
    owner VARCHAR(100),
    timeout INTEGER,
    created INTEGER,
    token VARCHAR(100),
    scope SMALLINT,
    depth SMALLINT,
    uri TEXT
);

ALTER TABLE ONLY locks
    ADD CONSTRAINT locks_pkey PRIMARY KEY (id);

CREATE INDEX locks_token_ix
    ON locks USING btree (token);

CREATE INDEX locks_uri_ix
    ON locks USING btree (uri);

CREATE TABLE principals (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    email VARCHAR(80),
    displayname VARCHAR(80)
);

ALTER TABLE ONLY principals
    ADD CONSTRAINT principals_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX principals_ukey
    ON principals USING btree (uri);

CREATE TABLE groupmembers (
    id SERIAL NOT NULL,
    principal_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL
);

ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX groupmembers_ukey
    ON groupmembers USING btree (principal_id, member_id);

CREATE TABLE propertystorage (
    id SERIAL NOT NULL,
    path VARCHAR(1024) NOT NULL,
    name VARCHAR(100) NOT NULL,
    valuetype INT,
    value BYTEA
);

ALTER TABLE ONLY propertystorage
    ADD CONSTRAINT propertystorage_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX propertystorage_ukey
    ON propertystorage (path, name);

CREATE TABLE users (
    id SERIAL NOT NULL,
    username VARCHAR(50),
    digesta1 VARCHAR(32)
);

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX users_ukey
    ON users USING btree (username);


CREATE TABLE oauth_clients (
  client_id             VARCHAR(80)   NOT NULL,
  client_secret         VARCHAR(80),
  redirect_uri          VARCHAR(2000),
  grant_types           VARCHAR(80),
  scope                 VARCHAR(4000),
  user_id               bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (client_id)
);

CREATE TABLE oauth_access_tokens (
  access_token         VARCHAR(40)    NOT NULL,
  client_id            VARCHAR(80)    NOT NULL,
  user_id              bigint NOT NULL DEFAULT '0',
  expires              TIMESTAMP      NOT NULL,
  scope                VARCHAR(4000),
  PRIMARY KEY (access_token)
);

CREATE TABLE oauth_authorization_codes (
  authorization_code  VARCHAR(40)     NOT NULL,
  client_id           VARCHAR(80)     NOT NULL,
  user_id             bigint NOT NULL DEFAULT '0',
  redirect_uri        VARCHAR(2000),
  expires             TIMESTAMP       NOT NULL,
  scope               VARCHAR(4000),
  id_token            VARCHAR(1000),
  PRIMARY KEY (authorization_code)
);

CREATE TABLE oauth_refresh_tokens (
  refresh_token       VARCHAR(40)     NOT NULL,
  client_id           VARCHAR(80)     NOT NULL,
  user_id             bigint NOT NULL DEFAULT '0',
  expires             TIMESTAMP       NOT NULL,
  scope               VARCHAR(4000),
  PRIMARY KEY (refresh_token)
);

CREATE TABLE oauth_scopes (
  scope               VARCHAR(191)    NOT NULL,
  is_default          SMALLINT,
  PRIMARY KEY (scope)
);

CREATE TABLE oauth_jwt (
  client_id           VARCHAR(80)     NOT NULL,
  subject             VARCHAR(80),
  public_key          VARCHAR(2000)   NOT NULL
);

CREATE TABLE IF NOT EXISTS workerq (
	workerq_id bigserial NOT NULL,
	workerq_priority smallint,
	workerq_reservationid varchar(25) DEFAULT NULL,
	workerq_processtimeout timestamp NOT NULL DEFAULT '0001-01-01 00:00:00',
	workerq_data text,
	workerq_uuid UUID NOT NULL,
	workerq_cmd text NOT NULL DEFAULT '',
	PRIMARY KEY (workerq_id)
);
CREATE INDEX idx_workerq_priority ON workerq (workerq_priority);
CREATE INDEX idx_workerq_reservationid ON workerq (workerq_reservationid);
CREATE INDEX idx_workerq_processtimeout ON workerq (workerq_processtimeout);
CREATE INDEX idx_workerq_uuid ON workerq (workerq_uuid);
