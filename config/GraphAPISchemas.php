<?php
return [
  'updateUserAssignLicenseJson' => '{
    "addLicenses": [
      {
        "disabledPlans": [],
        "skuId": "(User.OfficeLicense)"
      }
    ],
    "removeLicenses": []
  }',
  'createUserJson' => '{
    "accountEnabled": true, 
    "city": "Seattle",
    "country": "United States",
    "department": "Sales & Marketing",
    "displayName": "something",
    "givenName": "something",
    "jobTitle": "Marketing Director",
    "mailNickname": "MelissaD",
    "passwordPolicies": "DisablePasswordExpiration",
    "passwordProfile": {
      "password": "Test1234",
      "forceChangePasswordNextSignIn": false
    },
    "officeLocation": "131/1105",
    "postalCode": "98052",
    "preferredLanguage": "en-US",
    "state": "WA",
    "streetAddress": "9256 Towne Center Dr., Suite 400",
    "surname": "Darrow",
    "mobilePhone": "+1 206 555 0110",
    "usageLocation": "JP",
    "userPrincipalName": "something@naljp.onmicrosoft.com"
  }',
  'createGroupJson' => '{
    "description": "Group with designated owner and members",
    "displayName": "Operations group",
    "groupTypes": [
      "Unified"
    ],
    "mailEnabled": true,
    "mailNickname": "operations2019",
    "securityEnabled": false
  }',
  "userAttributes" => '[
    "accountEnabled",
    "ageGroup",
    "assignedLicenses",
    "assignedPlans",
    "businessPhones",
    "city",
    "companyName",
    "consentProvidedForMinor",
    "country",
    "department",
    "displayName",
    "employeeId",
    "faxNumber",
    "givenName",
    "imAddresses",
    "isResourceAccount",
    "jobTitle",
    "legalAgeGroupClassification",
    "licenseAssignmentStates",
    "mail",
    "mailNickname",
    "mobilePhone",
    "onPremisesDistinguishedName",
    "onPremisesImmutableId",
    "onPremisesLastSyncDateTime",
    "onPremisesSecurityIdentifier",
    "onPremisesSyncEnabled",
    "onPremisesDomainName",
    "onPremisesSamAccountName",
    "onPremisesUserPrincipalName",
    "otherMails",
    "passwordPolicies",
    "passwordProfile",
    "officeLocation",
    "postalCode",
    "postalCode",
    "preferredLanguage",
    "provisionedPlans",
    "proxyAddresses",
    "showInAddressList",
    "signInSessionsValidFromDateTime",
    "state",
    "streetAddress",
    "surname",
    "usageLocation",
    "userPrincipalName",
    "userType",
    "mailboxSettings",
    "aboutMe",
    "birthday",
    "hireDate",
    "interests",
    "mySite",
    "pastProjects",
    "preferredName",
    "responsibilities",
    "schools",
    "skills",
    "deviceEnrollmentLimit",
    "ownedDevices",
    "registeredDevices",
    "manager",
    "directReports",
    "memberOf",
    "createdObjects",
    "ownedObjects",
    "licenseDetails",
    "transitiveMemberOf",
    "extensions",
    "outlook",
    "messages",
    "mailFolders",
    "calendar",
    "calendarGroups",
    "calendarView",
    "events",
    "people",
    "contacts",
    "contactFolders",
    "inferenceClassification",
    "photo",
    "drive",
    "drives",
    "planner",
    "onenote",
    "managedDevices",
    "managedAppRegistrations",
    "deviceManagementTroubleshootingEvents",
    "activities",
    "insights",
    "settings",
    "joinedTeams"
  ]',
  "groupAttributes" => '[
    "assignedLicenses",
    "classification",
    "createdDateTime",
    "description",
    "displayName",
    "hasMembersWithLicenseErrors",
    "groupTypes",
    "licenseProcessingState",
    "mail",
    "mailEnabled",
    "mailNickname",
    "onPremisesLastSyncDateTime",
    "onPremisesProvisioningErrors",
    "onPremisesSecurityIdentifier",
    "onPremisesSyncEnabled",
    "preferredDataLocation",
    "proxyAddresses",
    "renewedDateTime",
    "securityEnabled",
    "visibility",
    "allowExternalSenders",
    "autoSubscribeNewMembers",
    "isSubscribedByMail",
    "unseenCount",
    "isArchived",
    "members",
    "memberOf",
    "membersWithLicenseErrors",
    "transitiveMembers",
    "transitiveMemberOf",
    "createdOnBehalfOf",
    "owners",
    "settings",
    "extensions",
    "threads",
    "calendar",
    "calendarView",
    "events",
    "conversations",
    "photo",
    "photos",
    "acceptedSenders",
    "rejectedSenders",
    "drive",
    "drives",
    "sites",
    "planner",
    "onenote",
    "groupLifecyclePolicies",
    "team" 
  ]'
];
